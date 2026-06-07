<?php
require_once __DIR__ . "/../includes/auth.php";

require_login();
require_permission("notify_priority_alerts");
validate_csrf();

try {
    $pdo->beginTransaction();

    /*
        Tomamos alertas críticas pendientes o notificadas.
        El objetivo es registrar que Gerencia pidió notificar a KAM.
    */
    $sqlAlerts = "
        SELECT
            alert_id,
            customer_id,
            customer_type,
            producto_en_riesgo,
            risk_score,
            alert_status
        FROM vw_cedis_dashboard_alerts
        WHERE risk_level = 'CRITICO'
          AND alert_status IN ('PENDIENTE', 'NOTIFICADO_CLIENTE')
        ORDER BY risk_score DESC, created_at DESC
        LIMIT 25
    ";

    $stmtAlerts = $pdo->query($sqlAlerts);
    $alerts = $stmtAlerts->fetchAll();

    if (count($alerts) === 0) {
        $pdo->rollBack();
        header("Location: " . BASE_URL . "dashboard_gerente.php?ok=1");
        exit;
    }

    $inserted = 0;

    foreach ($alerts as $alert) {
        $message = "Alerta prioritaria Order Rescue: cliente " .
                   ($alert["customer_id"] ?: "SIN_CLIENTE") .
                   ", producto en riesgo: " .
                   ($alert["producto_en_riesgo"] ?: "SIN_PRODUCTO") .
                   ", risk score: " .
                   $alert["risk_score"] . "%.";

        /*
            Evitar duplicar exactamente la misma notificación muchas veces el mismo día.
        */
        $sqlExists = "
            SELECT COUNT(*) AS total
            FROM notification_log
            WHERE alert_id = :alert_id
              AND channel = 'KAM'
              AND DATE(sent_at) = CURDATE()
        ";

        $stmtExists = $pdo->prepare($sqlExists);
        $stmtExists->execute([
            "alert_id" => $alert["alert_id"]
        ]);

        $exists = $stmtExists->fetch();

        if ($exists && (int)$exists["total"] > 0) {
            continue;
        }

        $sqlInsert = "
            INSERT INTO notification_log
            (
                alert_id,
                customer_id,
                channel,
                message,
                status,
                sent_by
            )
            VALUES
            (
                :alert_id,
                :customer_id,
                'KAM',
                :message,
                'ENVIADA',
                :sent_by
            )
        ";

        $stmtInsert = $pdo->prepare($sqlInsert);
        $stmtInsert->execute([
            "alert_id" => $alert["alert_id"],
            "customer_id" => $alert["customer_id"],
            "message" => $message,
            "sent_by" => $_SESSION["user_id"]
        ]);

        /*
            Marcamos alerta como notificada si estaba pendiente.
        */
        if ($alert["alert_status"] === "PENDIENTE") {
            $sqlUpdateAlert = "
                UPDATE risk_alerts
                SET alert_status = 'NOTIFICADO_CLIENTE',
                    updated_at = NOW()
                WHERE alert_id = :alert_id
            ";

            $stmtUpdateAlert = $pdo->prepare($sqlUpdateAlert);
            $stmtUpdateAlert->execute([
                "alert_id" => $alert["alert_id"]
            ]);
        }

        $inserted++;
    }

    log_audit(
        $pdo,
        $_SESSION["user_id"],
        "notification_log",
        "PRIORITY_ALERTS",
        "NOTIFY_PRIORITY_ALERTS",
        null,
        [
            "inserted_notifications" => $inserted,
            "channel" => "KAM"
        ]
    );

    $pdo->commit();

    header("Location: " . BASE_URL . "dashboard_gerente.php?ok=1");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    header("Location: " . BASE_URL . "dashboard_gerente.php?error=1");
    exit;
}
?>