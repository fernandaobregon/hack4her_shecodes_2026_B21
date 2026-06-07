<?php
require_once __DIR__ . "/../includes/auth.php";

require_login();
require_permission("manage_settings");
validate_csrf();

$allowedSettings = [
    "client_response_deadline" => [
        "label" => "Hora límite de respuesta del cliente",
        "default" => "09:00:00"
    ],
    "inventory_cut_time" => [
        "label" => "Hora sugerida de corte de inventario",
        "default" => "18:30:00"
    ],
    "notification_time" => [
        "label" => "Hora sugerida de notificación automática",
        "default" => "18:45:00"
    ],
    "auto_substitution_if_risk_over" => [
        "label" => "Umbral de riesgo para auto-sustitución",
        "default" => "90"
    ],
    "sku_tolerance_margin" => [
        "label" => "Margen mínimo de tolerancia en cajas",
        "default" => "2"
    ],
    "enable_auto_notifications" => [
        "label" => "Activar notificaciones automáticas",
        "default" => "0"
    ],
    "prioritize_vip_clients" => [
        "label" => "Priorizar clientes VIP",
        "default" => "0"
    ]
];

$oldValues = [];

$stmtOld = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
foreach ($stmtOld->fetchAll() as $row) {
    $oldValues[$row["setting_key"]] = $row["setting_value"];
}

$newValues = [];

try {
    $pdo->beginTransaction();

    foreach ($allowedSettings as $key => $config) {
        /*
            Para checkboxes:
            si no vienen en POST, significa apagado.
        */
        if (in_array($key, ["enable_auto_notifications", "prioritize_vip_clients"], true)) {
            $value = isset($_POST[$key]) ? "1" : "0";
        } else {
            $value = trim($_POST[$key] ?? $config["default"]);
        }

        /*
            Validaciones básicas
        */
        if (in_array($key, ["client_response_deadline", "inventory_cut_time", "notification_time"], true)) {
            if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value)) {
                throw new Exception("Formato de hora inválido para " . $key);
            }
        }

        if ($key === "auto_substitution_if_risk_over") {
            $number = (int)$value;
            if ($number < 0 || $number > 100) {
                throw new Exception("El umbral debe estar entre 0 y 100.");
            }
            $value = (string)$number;
        }

        if ($key === "sku_tolerance_margin") {
            $number = (int)$value;
            if ($number < 0) {
                throw new Exception("El margen no puede ser negativo.");
            }
            $value = (string)$number;
        }

        $newValues[$key] = $value;

        $sql = "INSERT INTO system_settings 
                (setting_key, setting_value, setting_label)
                VALUES 
                (:setting_key, :setting_value, :setting_label)
                ON DUPLICATE KEY UPDATE 
                    setting_value = VALUES(setting_value),
                    setting_label = VALUES(setting_label)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            "setting_key" => $key,
            "setting_value" => $value,
            "setting_label" => $config["label"]
        ]);
    }

    log_audit(
        $pdo,
        $_SESSION["user_id"],
        "system_settings",
        "GLOBAL",
        "UPDATE_SYSTEM_SETTINGS",
        $oldValues,
        $newValues
    );

    $pdo->commit();

    header("Location: " . BASE_URL . "dashboard_admin.php?ok=1");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    header("Location: " . BASE_URL . "dashboard_admin.php?error=1");
    exit;
}
?>