<?php
require_once __DIR__ . "/../includes/auth.php";

require_login();
require_permission("validate_inventory");
validate_csrf();

$productId = $_POST["product_id"] ?? null;
$cedisCode = trim($_POST["cedis_code"] ?? "");
$sapStock = $_POST["sap_stock"] ?? null;
$physicalStock = $_POST["physical_stock"] ?? null;
$notes = trim($_POST["notes"] ?? "");

if (!$productId || $cedisCode === "" || $sapStock === null || $physicalStock === null) {
    header("Location: " . BASE_URL . "dashboard_datos.php?error=1");
    exit;
}

$sapStock = (int)$sapStock;
$physicalStock = (int)$physicalStock;

if ($sapStock < 0 || $physicalStock < 0) {
    header("Location: " . BASE_URL . "dashboard_datos.php?error=1");
    exit;
}

/*
    Diferencia:
    Si SAP dice 17 y físico dice 8:
    difference_stock = physical - sap = -9
    Esto muestra déficit real de 9 piezas/cajas.
*/
$differenceStock = $physicalStock - $sapStock;

if ($differenceStock === 0) {
    $validationStatus = "CUADRA";
} else {
    $validationStatus = "DIFERENCIA";
}

/* Validar producto */
$stmtProduct = $pdo->prepare("SELECT product_id, product_name FROM products WHERE product_id = :product_id");
$stmtProduct->execute(["product_id" => $productId]);
$product = $stmtProduct->fetch();

if (!$product) {
    header("Location: " . BASE_URL . "dashboard_datos.php?error=1");
    exit;
}

/* Validar CEDIS */
$stmtCedis = $pdo->prepare("SELECT cedis_code FROM cedis WHERE cedis_code = :cedis_code");
$stmtCedis->execute(["cedis_code" => $cedisCode]);
$cedis = $stmtCedis->fetch();

if (!$cedis) {
    header("Location: " . BASE_URL . "dashboard_datos.php?error=1");
    exit;
}

try {
    $pdo->beginTransaction();

    $sql = "INSERT INTO inventory_validations
            (
                product_id,
                cedis_code,
                sap_stock,
                physical_stock,
                difference_stock,
                validation_status,
                validated_by,
                notes
            )
            VALUES
            (
                :product_id,
                :cedis_code,
                :sap_stock,
                :physical_stock,
                :difference_stock,
                :validation_status,
                :validated_by,
                :notes
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        "product_id" => $productId,
        "cedis_code" => $cedisCode,
        "sap_stock" => $sapStock,
        "physical_stock" => $physicalStock,
        "difference_stock" => $differenceStock,
        "validation_status" => $validationStatus,
        "validated_by" => $_SESSION["user_id"],
        "notes" => $notes
    ]);

    $validationId = $pdo->lastInsertId();

    /*
        Si hay diferencia, registramos un issue de calidad de datos.
        Esto permite que Gerencia/Admin puedan ver que existe una inconsistencia real.
    */
    if ($differenceStock !== 0) {
        $severity = abs($differenceStock) >= 10 ? "ALTA" : "MEDIA";

        $description = "Diferencia SAP vs físico en CEDIS " . $cedisCode .
                       ". Producto: " . $product["product_name"] .
                       ". SAP: " . $sapStock .
                       ", físico: " . $physicalStock .
                       ", diferencia: " . $differenceStock . ".";

        $sqlIssue = "INSERT INTO data_quality_issues
                     (
                        issue_type,
                        entity_name,
                        entity_id,
                        description,
                        severity
                     )
                     VALUES
                     (
                        :issue_type,
                        :entity_name,
                        :entity_id,
                        :description,
                        :severity
                     )";

        $stmtIssue = $pdo->prepare($sqlIssue);
        $stmtIssue->execute([
            "issue_type" => "DIFERENCIA_INVENTARIO",
            "entity_name" => "inventory_validations",
            "entity_id" => $validationId,
            "description" => $description,
            "severity" => $severity
        ]);
    }

    log_audit(
        $pdo,
        $_SESSION["user_id"],
        "inventory_validations",
        $validationId,
        "CREATE_INVENTORY_VALIDATION",
        null,
        [
            "product_id" => $productId,
            "product_name" => $product["product_name"],
            "cedis_code" => $cedisCode,
            "sap_stock" => $sapStock,
            "physical_stock" => $physicalStock,
            "difference_stock" => $differenceStock,
            "validation_status" => $validationStatus
        ]
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