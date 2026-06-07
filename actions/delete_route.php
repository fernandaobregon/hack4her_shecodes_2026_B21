<?php
require_once __DIR__ . "/../includes/auth.php";

require_login();
require_permission("manage_routes");
validate_csrf();

$routeId = trim($_POST["route_id"] ?? "");

if ($routeId === "") {
    header("Location: " . BASE_URL . "dashboard_logistica.php?error=1");
    exit;
}

try {
    $pdo->beginTransaction();

    $stmtOld = $pdo->prepare("SELECT * FROM delivery_routes WHERE route_id = :route_id");
    $stmtOld->execute(["route_id" => $routeId]);
    $oldRoute = $stmtOld->fetch();

    if (!$oldRoute) {
        throw new Exception("Ruta no encontrada.");
    }

    /*
        Primero se eliminan asignaciones de pedidos para no romper llaves foráneas.
    */
    $stmtOrders = $pdo->prepare("DELETE FROM route_orders WHERE route_id = :route_id");
    $stmtOrders->execute(["route_id" => $routeId]);

    $stmtRoute = $pdo->prepare("DELETE FROM delivery_routes WHERE route_id = :route_id");
    $stmtRoute->execute(["route_id" => $routeId]);

    log_audit(
        $pdo,
        $_SESSION["user_id"],
        "delivery_routes",
        $routeId,
        "DELETE_ROUTE",
        $oldRoute,
        null
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