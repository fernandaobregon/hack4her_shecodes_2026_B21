<?php
require_once "includes/auth.php";

require_login();
require_permission("view_logistics_dashboard");

$pageTitle = "Dashboard Logística";
$pageSubtitle = "Módulo de control logístico y rutas";
$activePage = "logistica";

$user = current_user();
$csrfToken = create_csrf_token();

$canConfirmCut = user_has_permission("confirm_inventory_cut");
$canManageRoutes = user_has_permission("manage_routes");

/* =========================================================
   Cargar CEDIS disponibles
   ========================================================= */

$sqlCedis = "SELECT cedis_code, country, region, city
             FROM cedis
             ORDER BY cedis_code";

$cedisList = $pdo->query($sqlCedis)->fetchAll();

/* =========================================================
   Último corte de inventario
   ========================================================= */

$sqlLastCut = "SELECT 
                    ic.inventory_cut_id,
                    ic.cedis_code,
                    ic.cut_datetime,
                    ic.source_system,
                    ic.status,
                    u.full_name AS uploaded_by_name
               FROM inventory_cuts ic
               LEFT JOIN users u ON u.user_id = ic.uploaded_by
               ORDER BY ic.cut_datetime DESC
               LIMIT 1";

$lastCut = $pdo->query($sqlLastCut)->fetch();

/* =========================================================
   KPIs de rutas
   ========================================================= */

$sqlRouteKpis = "SELECT
                    COUNT(*) AS total_rutas,
                    SUM(CASE WHEN max_risk_score >= 70 THEN 1 ELSE 0 END) AS rutas_criticas,
                    SUM(COALESCE(pedidos_incompletos_o_riesgo, 0)) AS pedidos_en_riesgo,
                    ROUND(AVG(COALESCE(progress_percent, 0)), 1) AS progreso_promedio
                 FROM vw_logistics_routes_summary";

$routeKpis = $pdo->query($sqlRouteKpis)->fetch();

if (!$routeKpis) {
    $routeKpis = [
        "total_rutas" => 0,
        "rutas_criticas" => 0,
        "pedidos_en_riesgo" => 0,
        "progreso_promedio" => 0
    ];
}

/* =========================================================
   Rutas de reparto
   ========================================================= */

$sqlRoutes = "SELECT
                    route_id,
                    route_name,
                    driver_name,
                    vehicle_info,
                    cedis_code,
                    progress_percent,
                    route_status,
                    total_pedidos,
                    pedidos_incompletos_o_riesgo,
                    max_risk_score
              FROM vw_logistics_routes_summary
              ORDER BY 
                CASE 
                    WHEN max_risk_score >= 70 THEN 1
                    WHEN max_risk_score >= 30 THEN 2
                    ELSE 3
                END,
                pedidos_incompletos_o_riesgo DESC,
                route_id ASC";

$routes = $pdo->query($sqlRoutes)->fetchAll();

include "includes/header.php";
?>

<div class="page-title">
    <h2>Vista Logística</h2>
    <p>Control de cortes de inventario, rutas críticas y preparación de salidas.</p>
</div>

<div class="card card-highlight">
    <div class="card-title">
        <div>
            <h3>Corte de inventario diario</h3>
            <p>
                Registra el momento en que logística valida el inventario real del CEDIS
                para comparar stock contra pedidos pendientes.
            </p>
        </div>
    </div>

    <div class="grid-2">
        <div>
            <div class="info-box">
                <span class="info-label">Último corte registrado</span>

                <?php if ($lastCut): ?>
                    <strong>
                        <?php echo e($lastCut["cut_datetime"]); ?>
                    </strong>

                    <p>
                        CEDIS: <strong><?php echo e($lastCut["cedis_code"]); ?></strong><br>
                        Estado: <strong><?php echo e($lastCut["status"]); ?></strong><br>
                        Usuario: <strong><?php echo e($lastCut["uploaded_by_name"] ?? "Sistema"); ?></strong>
                    </p>
                <?php else: ?>
                    <strong>Sin cortes registrados</strong>
                    <p>Confirma el primer corte para iniciar el flujo operativo.</p>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <?php if ($canConfirmCut): ?>
                <form method="POST" action="actions/confirm_inventory_cut.php" class="cut-form">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">

                    <div class="form-group">
                        <label>CEDIS a confirmar</label>
                        <select name="cedis_code" required>
                            <option value="">Selecciona un CEDIS</option>

                            <?php foreach ($cedisList as $cedis): ?>
                                <option value="<?php echo e($cedis["cedis_code"]); ?>">
                                    CEDIS <?php echo e($cedis["cedis_code"]); ?>
                                    <?php if (!empty($cedis["city"])): ?>
                                        - <?php echo e($cedis["city"]); ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button class="btn btn-red" type="submit">
                        Confirmar corte de inventario
                    </button>
                </form>
            <?php else: ?>
                <div class="status-pill">
                    No tienes permiso para confirmar cortes.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="grid-4">
    <div class="kpi-card">
        <div class="kpi-label">Total rutas</div>
        <div class="kpi-value red">
            <?php echo e($routeKpis["total_rutas"] ?? 0); ?>
        </div>
        <div class="kpi-note">Rutas registradas</div>
    </div>

    <div class="kpi-card orange">
        <div class="kpi-label">Rutas críticas</div>
        <div class="kpi-value orange">
            <?php echo e($routeKpis["rutas_criticas"] ?? 0); ?>
        </div>
        <div class="kpi-note">Con riesgo alto de entrega incompleta</div>
    </div>

    <div class="kpi-card">
        <div class="kpi-label">Pedidos en riesgo</div>
        <div class="kpi-value red">
            <?php echo e($routeKpis["pedidos_en_riesgo"] ?? 0); ?>
        </div>
        <div class="kpi-note">Pedidos incompletos o con alerta</div>
    </div>

    <div class="kpi-card green">
        <div class="kpi-label">Progreso promedio</div>
        <div class="kpi-value">
            <?php echo e($routeKpis["progreso_promedio"] ?? 0); ?>%
        </div>
        <div class="kpi-note">Avance operativo de carga</div>
    </div>
</div>

<div class="card">
    <div class="card-title">
        <div>
            <h3>Estructura y planificación de rutas de reparto</h3>
            <p>Rutas priorizadas por pedidos incompletos, progreso de surtido y nivel de riesgo.</p>
        </div>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID ruta</th>
                    <th>Conductor / unidad</th>
                    <th>CEDIS</th>
                    <th>Pedidos</th>
                    <th>Riesgo</th>
                    <th>Progreso</th>
                    <th>Estatus</th>

                    <?php if ($canManageRoutes): ?>
                        <th>Editar</th>
                        <th>Eliminar</th>
                    <?php endif; ?>
                </tr>
            </thead>

            <tbody>
                <?php if (count($routes) === 0): ?>
                    <tr>
                        <td colspan="<?php echo $canManageRoutes ? 9 : 7; ?>">
                            No hay rutas registradas todavía.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($routes as $route): ?>
                    <?php
                        $riskScore = (int)($route["max_risk_score"] ?? 0);

                        if ($riskScore >= 70) {
                            $riskLevel = "CRITICO";
                        } elseif ($riskScore >= 30) {
                            $riskLevel = "MEDIO";
                        } else {
                            $riskLevel = "BAJO";
                        }

                        $progress = (float)($route["progress_percent"] ?? 0);
                    ?>

                    <tr>
                        <td class="table-id">
                            <?php echo e($route["route_id"]); ?>
                        </td>

                        <td>
                            <strong><?php echo e($route["driver_name"] ?: "Sin conductor"); ?></strong><br>
                            <small><?php echo e($route["vehicle_info"] ?: "Sin unidad asignada"); ?></small>
                        </td>

                        <td>
                            CEDIS <?php echo e($route["cedis_code"]); ?>
                        </td>

                        <td>
                            <strong>
                                <?php echo e($route["pedidos_incompletos_o_riesgo"] ?? 0); ?> pedidos en riesgo
                            </strong><br>
                            <small>
                                <?php echo e($route["total_pedidos"] ?? 0); ?> pedidos asignados
                            </small>
                        </td>

                        <td>
                            <span class="badge <?php echo risk_badge_class($riskLevel); ?>">
                                <?php echo e($riskLevel); ?>
                                (<?php echo e($riskScore); ?>%)
                            </span>
                        </td>

                        <td>
                            <div class="bar-row small-bar">
                                <div class="bar-label">
                                    <span><?php echo e($progress); ?>%</span>
                                </div>
                                <div class="bar-track">
                                    <div 
                                        class="bar-fill <?php echo $riskLevel === 'CRITICO' ? '' : ($riskLevel === 'MEDIO' ? 'orange' : 'green'); ?>"
                                        style="width: <?php echo min(100, max(0, $progress)); ?>%;"
                                    ></div>
                                </div>
                            </div>
                        </td>

                        <td>
                            <?php
                                $statusClass = "status-pill";

                                if ($route["route_status"] === "LISTA_DESPACHO" || $route["route_status"] === "CERRADA") {
                                    $statusClass .= " status-green";
                                } elseif ($route["route_status"] === "RETENIDA") {
                                    $statusClass .= " status-yellow";
                                } else {
                                    $statusClass .= " status-blue";
                                }
                            ?>

                            <span class="<?php echo $statusClass; ?>">
                                <?php echo e($route["route_status"]); ?>
                            </span>
                        </td>

                        <?php if ($canManageRoutes): ?>
                            <td>
                                <form method="POST" action="actions/save_route.php" class="route-edit-form">
                                    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                    <input type="hidden" name="original_route_id" value="<?php echo e($route["route_id"]); ?>">

                                    <input 
                                        type="hidden" 
                                        name="route_id" 
                                        value="<?php echo e($route["route_id"]); ?>"
                                    >

                                    <input 
                                        type="hidden" 
                                        name="route_name" 
                                        value="<?php echo e($route["route_name"]); ?>"
                                    >

                                    <input 
                                        type="hidden" 
                                        name="driver_name" 
                                        value="<?php echo e($route["driver_name"]); ?>"
                                    >

                                    <input 
                                        type="hidden" 
                                        name="vehicle_info" 
                                        value="<?php echo e($route["vehicle_info"]); ?>"
                                    >

                                    <input 
                                        type="hidden" 
                                        name="cedis_code" 
                                        value="<?php echo e($route["cedis_code"]); ?>"
                                    >

                                    <div class="form-inline">
                                        <input 
                                            type="number" 
                                            name="progress_percent" 
                                            min="0" 
                                            max="100" 
                                            step="1"
                                            value="<?php echo e($route["progress_percent"]); ?>"
                                            style="width:80px;"
                                        >

                                        <select name="route_status">
                                            <option value="PENDIENTE" <?php echo $route["route_status"] === "PENDIENTE" ? "selected" : ""; ?>>
                                                Pendiente
                                            </option>
                                            <option value="EN_PREPARACION" <?php echo $route["route_status"] === "EN_PREPARACION" ? "selected" : ""; ?>>
                                                En preparación
                                            </option>
                                            <option value="RETENIDA" <?php echo $route["route_status"] === "RETENIDA" ? "selected" : ""; ?>>
                                                Retenida
                                            </option>
                                            <option value="LISTA_DESPACHO" <?php echo $route["route_status"] === "LISTA_DESPACHO" ? "selected" : ""; ?>>
                                                Lista despacho
                                            </option>
                                            <option value="EN_RUTA" <?php echo $route["route_status"] === "EN_RUTA" ? "selected" : ""; ?>>
                                                En ruta
                                            </option>
                                            <option value="CERRADA" <?php echo $route["route_status"] === "CERRADA" ? "selected" : ""; ?>>
                                                Cerrada
                                            </option>
                                        </select>

                                        <button class="btn btn-red" type="submit">
                                            Guardar
                                        </button>
                                    </div>
                                </form>
                            </td>

                            <td>
                                <form method="POST" action="actions/delete_route.php" onsubmit="return confirm('¿Seguro que deseas eliminar esta ruta?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                    <input type="hidden" name="route_id" value="<?php echo e($route["route_id"]); ?>">

                                    <button class="btn btn-light" type="submit">
                                        Eliminar
                                    </button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($canManageRoutes): ?>
    <div class="card">
        <div class="card-title">
            <div>
                <h3>Crear nueva ruta</h3>
                <p>Registra una ruta logística para monitorear pedidos incompletos o en riesgo.</p>
            </div>
        </div>

        <form method="POST" action="actions/save_route.php">
            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">

            <div class="form-row">
                <div class="form-group">
                    <label>ID de ruta</label>
                    <input type="text" name="route_id" placeholder="RUTA-NORTE-02" required>
                </div>

                <div class="form-group">
                    <label>Nombre de ruta</label>
                    <input type="text" name="route_name" placeholder="Ruta Norte 02" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Conductor</label>
                    <input type="text" name="driver_name" placeholder="Nombre del conductor">
                </div>

                <div class="form-group">
                    <label>Unidad / vehículo</label>
                    <input type="text" name="vehicle_info" placeholder="Camión Isuzu - Placas XYZ-123">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>CEDIS</label>
                    <select name="cedis_code" required>
                        <option value="">Selecciona un CEDIS</option>

                        <?php foreach ($cedisList as $cedis): ?>
                            <option value="<?php echo e($cedis["cedis_code"]); ?>">
                                CEDIS <?php echo e($cedis["cedis_code"]); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Progreso de carga (%)</label>
                    <input type="number" name="progress_percent" min="0" max="100" value="0" required>
                </div>
            </div>

            <div class="form-group">
                <label>Estatus de ruta</label>
                <select name="route_status" required>
                    <option value="PENDIENTE">Pendiente</option>
                    <option value="EN_PREPARACION">En preparación</option>
                    <option value="RETENIDA">Retenida</option>
                    <option value="LISTA_DESPACHO">Lista para despacho</option>
                    <option value="EN_RUTA">En ruta</option>
                    <option value="CERRADA">Cerrada</option>
                </select>
            </div>

            <button class="btn btn-red" type="submit">
                Guardar nueva ruta
            </button>
        </form>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-title">
        <div>
            <h3>¿Por qué esta vista importa?</h3>
            <p>Logística controla el momento operativo donde Order Rescue se vuelve accionable.</p>
        </div>
    </div>

    <p>
        Esta vista permite registrar el corte de inventario y revisar rutas con pedidos incompletos o en riesgo.
        El objetivo es evitar que una unidad salga a reparto con sustituciones no confirmadas por el cliente.
    </p>

    <p>
        Los cambios se guardan directamente en MySQL y pueden ser consultados por CEDIS, gerencia y el módulo móvil.
    </p>
</div>

<?php include "includes/footer.php"; ?>