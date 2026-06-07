<?php

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect($path) {
    header("Location: " . $path);
    exit;
}

function risk_badge_class($riskLevel) {
    switch ($riskLevel) {
        case 'CRITICO':
            return 'badge-critical';
        case 'MEDIO':
            return 'badge-medium';
        case 'BAJO':
            return 'badge-low';
        default:
            return 'badge-neutral';
    }
}

function status_label($status) {
    switch ($status) {
        case 'PENDIENTE':
            return 'Esperando acción';
        case 'NOTIFICADO_CLIENTE':
            return 'Esperando respuesta del cliente';
        case 'RESPONDIDO':
            return 'Cliente respondió';
        case 'RESUELTO':
            return 'Listo para despacho';
        case 'VENCIDO':
            return 'Tiempo vencido';
        default:
            return $status;
    }
}

function create_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function validate_csrf() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $token = $_POST['csrf_token'] ?? '';

    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        die("Token CSRF inválido. Recarga la página e intenta de nuevo.");
    }
}

function log_audit($pdo, $userId, $entityName, $entityId, $action, $oldValue = null, $newValue = null) {
    $sql = "INSERT INTO audit_log 
            (user_id, entity_name, entity_id, action, old_value, new_value)
            VALUES 
            (:user_id, :entity_name, :entity_id, :action, :old_value, :new_value)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        "user_id" => $userId,
        "entity_name" => $entityName,
        "entity_id" => $entityId,
        "action" => $action,
        "old_value" => $oldValue ? json_encode($oldValue, JSON_UNESCAPED_UNICODE) : null,
        "new_value" => $newValue ? json_encode($newValue, JSON_UNESCAPED_UNICODE) : null
    ]);
}
?>