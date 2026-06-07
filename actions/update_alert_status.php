<?php
require_once __DIR__ . "/../includes/auth.php";

require_login();
require_permission("update_alert_status");
validate_csrf();

$alertId = $_POST["alert_id"] ?? null;
$newStatus = $_POST["alert_status"] ?? null;

$allowedStatuses = [
    "PENDIENTE",
    "NOTIFICADO_CLIENTE",
    "RESPONDIDO",
    "RESUELTO",
    "VENCIDO"
];

if (!$alertId || !in_array($newStatus, $allowedStatuses, true)) {
    header("Location: " . BASE_URL . "dashboard_cedis.php?error=1");
    exit;
}

/* Obtener valor anterior para auditoría */
$sqlOld = "SELECT alert_id, alert_status, resolved_at 
           FROM risk_alerts 
           WHERE alert_id = :alert_id";

$stmtOld = $pdo->prepare($sqlOld);
$stmtOld->execute(["alert_id" => $alertId]);
$old = $stmtOld->fetch();

if (!$old) {
    header("Location: " . BASE_URL . "dashboard_cedis.php?error=1");
    exit;
}

$resolvedAtSql = $newStatus === "RESUELTO" ? "resolved_at = NOW()," : "resolved_at = NULL,";

$sql = "UPDATE risk_alerts
        SET alert_status = :alert_status,
            $resolvedAtSql
            updated_at = NOW()
        WHERE alert_id = :alert_id";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        "alert_status" => $newStatus,
        "alert_id" => $alertId
    ]);

    log_audit(
        $pdo,
        $_SESSION["user_id"],
        "risk_alerts",
        $alertId,
        "UPDATE_ALERT_STATUS",
        $old,
        ["alert_status" => $newStatus]
    );

    header("Location: " . BASE_URL . "dashboard_cedis.php?ok=1");
    exit;

} catch (PDOException $e) {
    header("Location: " . BASE_URL . "dashboard_cedis.php?error=1");
    exit;
}
?>