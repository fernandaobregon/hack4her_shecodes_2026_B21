<?php
require_once __DIR__ . "/../includes/auth.php";

require_login();
require_permission("manage_routes");
validate_csrf();

$originalRouteId = trim($_POST["original_route_id"] ?? "");
$routeId = trim($_POST["route_id"] ?? "");
$routeName = trim($_POST["route_name"] ?? "");
$driverName = trim($_POST["driver_name"] ?? "");
$vehicleInfo = trim($_POST["vehicle_info"] ?? "");
$cedisCode = trim($_POST["cedis_code"] ?? "");
$progressPercent = $_POST["progress_percent"] ?? 0;
$routeStatus = $_POST["route_status"] ?? "PENDIENTE";

$allowedStatuses = [
    "PENDIENTE",
    "EN_PREPARACION",
    "RETENIDA",
    "LISTA_DESPACHO",
    "EN_RUTA",
    "CERRADA"
];

if (
    $routeId === "" ||
    $routeName === "" ||
    $cedisCode === "" ||
    !in_array($routeStatus, $allowedStatuses, true)
) {
    header("Location: " . BASE_URL . "dashboard_logistica.php?error=1");
    exit;
}

$progressPercent = (float)$progressPercent;

if ($progressPercent < 0) {
    $progressPercent = 0;
}

if ($progressPercent > 100) {
    $progressPercent = 100;
}

/* Validar CEDIS */
$stmtCedis = $pdo->prepare("SELECT cedis_code FROM cedis WHERE cedis_code = :cedis_code");
$stmtCedis->execute(["cedis_code" => $cedisCode]);
$cedis = $stmtCedis->fetch();

if (!$cedis) {
    header("Location: " . BASE_URL . "dashboard_logistica.php?error=1");
    exit;
}

try {
    $pdo->beginTransaction();

    /*
        Si originalRouteId viene, es edición.
        Si no viene, es creación.
    */

    if ($originalRouteId !== "") {
        $stmtOld = $pdo->prepare("SELECT * FROM delivery_routes WHERE route_id = :route_id");
        $stmtOld->execute(["route_id" => $originalRouteId]);
        $oldRoute = $stmtOld->fetch();

        if (!$oldRoute) {
            throw new Exception("Ruta original no encontrada.");
        }

        /*
            Por seguridad no permitimos cambiar el route_id en edición.
            Se mantiene el original.
        */
        $sql = "UPDATE delivery_routes
                SET route_name = :route_name,
                    driver_name = :driver_name,
                    vehicle_info = :vehicle_info,
                    cedis_code = :cedis_code,
                    progress_percent = :progress_percent,
                    route_status = :route_status
                WHERE route_id = :original_route_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            "route_name" => $routeName,
            "driver_name" => $driverName,
            "vehicle_info" => $vehicleInfo,
            "cedis_code" => $cedisCode,
            "progress_percent" => $progressPercent,
            "route_status" => $routeStatus,
            "original_route_id" => $originalRouteId
        ]);

        log_audit(
            $pdo,
            $_SESSION["user_id"],
            "delivery_routes",
            $originalRouteId,
            "UPDATE_ROUTE",
            $oldRoute,
            [
                "route_name" => $routeName,
                "driver_name" => $driverName,
                "vehicle_info" => $vehicleInfo,
                "cedis_code" => $cedisCode,
                "progress_percent" => $progressPercent,
                "route_status" => $routeStatus
            ]
        );

    } else {
        $sql = "INSERT INTO delivery_routes
                (route_id, route_name, driver_name, vehicle_info, cedis_code, progress_percent, route_status)
                VALUES
                (:route_id, :route_name, :driver_name, :vehicle_info, :cedis_code, :progress_percent, :route_status)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            "route_id" => $routeId,
            "route_name" => $routeName,
            "driver_name" => $driverName,
            "vehicle_info" => $vehicleInfo,
            "cedis_code" => $cedisCode,
            "progress_percent" => $progressPercent,
            "route_status" => $routeStatus
        ]);

        log_audit(
            $pdo,
            $_SESSION["user_id"],
            "delivery_routes",
            $routeId,
            "CREATE_ROUTE",
            null,
            [
                "route_id" => $routeId,
                "route_name" => $routeName,
                "driver_name" => $driverName,
                "vehicle_info" => $vehicleInfo,
                "cedis_code" => $cedisCode,
                "progress_percent" => $progressPercent,
                "route_status" => $routeStatus
            ]
        );
    }

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