<?php
require_once __DIR__ . "/../includes/auth.php";

require_login();
require_permission("delete_inventory_validation");
validate_csrf();

$validationId = $_POST["validation_id"] ?? null;

if (!$validationId) {
    header("Location: " . BASE_URL . "dashboard_datos.php?error=1");
    exit;
}

try {
    $pdo->beginTransaction();

    $stmtOld = $pdo->prepare("SELECT * FROM inventory_validations WHERE validation_id = :validation_id");
    $stmtOld->execute(["validation_id" => $validationId]);
    $oldValidation = $stmtOld->fetch();

    if (!$oldValidation) {
        throw new Exception("Validación no encontrada.");
    }

    /*
        Primero eliminamos issues relacionados con esa validación.
        Esto evita dejar errores huérfanos en data_quality_issues.
    */
    $stmtIssues = $pdo->prepare("
        DELETE FROM data_quality_issues
        WHERE entity_name = 'inventory_validations'
          AND entity_id = :validation_id
    ");

    $stmtIssues->execute([
        "validation_id" => $validationId
    ]);

    $stmtDelete = $pdo->prepare("
        DELETE FROM inventory_validations
        WHERE validation_id = :validation_id
    ");

    $stmtDelete->execute([
        "validation_id" => $validationId
    ]);

    log_audit(
        $pdo,
        $_SESSION["user_id"],
        "inventory_validations",
        $validationId,
        "DELETE_INVENTORY_VALIDATION",
        $oldValidation,
        null
    );

    $pdo->commit();

    header("Location: " . BASE_URL . "dashboard_datos.php?ok=1");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    header("Location: " . BASE_URL . "dashboard_datos.php?error=1");
    exit;
}
?>