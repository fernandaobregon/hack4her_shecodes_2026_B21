<?php
require_once __DIR__ . "/../includes/auth.php";

require_login();
require_permission("confirm_inventory_cut");
validate_csrf();

$cedisCode = trim($_POST["cedis_code"] ?? "");

if ($cedisCode === "") {
    header("Location: " . BASE_URL . "dashboard_logistica.php?error=1");
    exit;
}

/* Validar que el CEDIS exista */
$stmtCedis = $pdo->prepare("SELECT cedis_code FROM cedis WHERE cedis_code = :cedis_code");
$stmtCedis->execute(["cedis_code" => $cedisCode]);
$cedis = $stmtCedis->fetch();

if (!$cedis) {
    header("Location: " . BASE_URL . "dashboard_logistica.php?error=1");
    exit;
}

try {
    $pdo->beginTransaction();

    $sql = "INSERT INTO inventory_cuts
            (cedis_code, cut_datetime, source_system, uploaded_by, status)
            VALUES
            (:cedis_code, NOW(), :source_system, :uploaded_by, 'VALIDADO')";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        "cedis_code" => $cedisCode,
        "source_system" => "dashboard_logistica",
        "uploaded_by" => $_SESSION["user_id"]
    ]);

    $inventoryCutId = $pdo->lastInsertId();

    log_audit(
        $pdo,
        $_SESSION["user_id"],
        "inventory_cuts",
        $inventoryCutId,
        "CONFIRM_INVENTORY_CUT",
        null,
        [
            "cedis_code" => $cedisCode,
            "status" => "VALIDADO"
        ]
    );

    $pdo->commit();

    header("Location: " . BASE_URL . "dashboard_logistica.php?ok=1");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    header("Location: " . BASE_URL . "dashboard_logistica.php?error=1");
    exit;
}
?>