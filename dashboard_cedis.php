<?php
require_once "includes/auth.php";

require_login();
require_permission("view_cedis_dashboard");

$pageTitle = "Vista CEDIS";
$pageSubtitle = "Panel operativo CEDIS";
$activePage = "cedis";

$user = current_user();

/*
    KPIs principales para la vista CEDIS.
    Se alimentan de vw_cedis_dashboard_alerts y vw_cedis_stock_critical.
*/

$sqlKpis = "
SELECT
    COUNT(*) AS total_alertas,
    SUM(CASE WHEN risk_level = 'CRITICO' THEN 1 ELSE 0 END) AS criticas,
    SUM(CASE WHEN risk_level = 'MEDIO' THEN 1 ELSE 0 END) AS medias,
    SUM(CASE WHEN alert_status = 'RESUELTO' THEN 1 ELSE 0 END) AS resueltas
FROM vw_cedis_dashboard_alerts
";

$kpis = $pdo->query($sqlKpis)->fetch();

$sqlCriticalStock = "
SELECT
    cedis_code,
    product_name,
    pedidos_en_riesgo,
    alertas_criticas,
    cantidad_comprometida,
    deficit_estimado
FROM vw_cedis_stock_critical
LIMIT 4
";

$criticalStock = $pdo->query($sqlCriticalStock)->fetchAll();

$sqlAlerts = "
SELECT
    alert_id,
    pedido,
    cedis_code,
    customer_id,
    customer_segment,
    customer_type,
    producto_en_riesgo,
    quantity,
    risk_score,
    risk_level,
    alert_status,
    causa_probable,
    deadline_response,
    created_at
FROM vw_cedis_dashboard_alerts
ORDER BY 
    CASE risk_level
        WHEN 'CRITICO' THEN 1
        WHEN 'MEDIO' THEN 2
        WHEN 'BAJO' THEN 3
        ELSE 4
    END,
    risk_score DESC,
    created_at DESC
LIMIT 30
";

$alerts = $pdo->query($sqlAlerts)->fetchAll();

$canUpdateAlert = user_has_permission("update_alert_status");

include "includes/header.php";
?>

<div class="page-title">
    <h2>Vista CEDIS / Supervisor de Almacén</h2>
    <p>Monitoreo de pedidos en riesgo, SKUs críticos y estatus de preparación antes del despacho.</p>
</div>

<div class="grid-4">
    <div class="kpi-card">
        <div class="kpi-label">Alertas activas</div>
        <div class="kpi-value red"><?php echo e($kpis["total_alertas"] ?? 0); ?></div>
        <div class="kpi-note">Pedidos con riesgo o seguimiento</div>
    </div>

    <div class="kpi-card">
        <div class="kpi-label">Riesgo crítico</div>
        <div class="kpi-value red"><?php echo e($kpis["criticas"] ?? 0); ?></div>
        <div class="kpi-note">Requieren acción prioritaria</div>
    </div>

    <div class="kpi-card orange">
        <div class="kpi-label">Riesgo medio</div>
        <div class="kpi-value orange"><?php echo e($kpis["medias"] ?? 0); ?></div>
        <div class="kpi-note">Monitoreo operativo</div>
    </div>

    <div class="kpi-card green">
        <div class="kpi-label">Resueltas</div>
        <div class="kpi-value"><?php echo e($kpis["resueltas"] ?? 0); ?></div>
        <div class="kpi-note">Listas para despacho</div>
    </div>
</div>

<div class="grid-2">
    <?php if (count($criticalStock) > 0): ?>
        <?php foreach ($criticalStock as $index => $stock): ?>
            <div class="card kpi <?php echo $index % 2 === 0 ? '' : 'orange'; ?>">
                <div class="kpi-label">
                    Stock crítico: <?php echo e($stock["product_name"]); ?>
                </div>

                <div class="kpi-value <?php echo $index % 2 === 0 ? 'red' : 'orange'; ?>">
                    Déficit: <?php echo e($stock["deficit_estimado"]); ?> cajas
                </div>

                <div class="kpi-note">
                    CEDIS: <?php echo e($stock["cedis_code"]); ?> |
                    Pedidos en riesgo: <?php echo e($stock["pedidos_en_riesgo"]); ?> |
                    Comprometido: <?php echo e($stock["cantidad_comprometida"]); ?> cajas
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="card">
            <h3>Sin stock crítico registrado</h3>
            <p>No hay alertas suficientes para calcular stock crítico.</p>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-title">
        <div>
            <h3>Pedidos pendientes de preparación antes del despacho</h3>
            <p>Priorizados por nivel de riesgo y probabilidad de sustitución.</p>
        </div>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID alerta</th>
                    <th>Pedido</th>
                    <th>CEDIS</th>
                    <th>Cliente B2B</th>
                    <th>Producto en riesgo</th>
                    <th>Cantidad</th>
                    <th>Nivel de riesgo</th>
                    <th>Estatus de acción</th>
                    <?php if ($canUpdateAlert): ?>
                        <th>Actualizar</th>
                    <?php endif; ?>
                </tr>
            </thead>

            <tbody>
                <?php if (count($alerts) === 0): ?>
                    <tr>
                        <td colspan="<?php echo $canUpdateAlert ? 9 : 8; ?>">
                            No hay alertas registradas.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($alerts as $alert): ?>
                    <tr>
                        <td class="table-id">#<?php echo e($alert["alert_id"]); ?></td>

                        <td>
                            <?php echo e($alert["pedido"] ?: "Sin pedido único"); ?>
                        </td>

                        <td>
                            <?php echo e($alert["cedis_code"] ?: "N/A"); ?>
                        </td>

                        <td>
                            <strong><?php echo e($alert["customer_type"]); ?></strong><br>
                            <small><?php echo e($alert["customer_id"]); ?></small>
                        </td>

                        <td>
                            <?php echo e($alert["producto_en_riesgo"]); ?>
                        </td>

                        <td>
                            <?php echo e($alert["quantity"]); ?>
                        </td>

                        <td>
                            <span class="badge <?php echo risk_badge_class($alert["risk_level"]); ?>">
                                <?php echo e($alert["risk_level"]); ?> 
                                (<?php echo e($alert["risk_score"]); ?>%)
                            </span>
                        </td>

                        <td>
                            <?php
                                $statusClass = "status-pill";

                                if ($alert["alert_status"] === "RESUELTO") {
                                    $statusClass .= " status-green";
                                } elseif ($alert["alert_status"] === "NOTIFICADO_CLIENTE") {
                                    $statusClass .= " status-blue";
                                } elseif ($alert["alert_status"] === "PENDIENTE") {
                                    $statusClass .= " status-yellow";
                                }
                            ?>

                            <span class="<?php echo $statusClass; ?>">
                                <?php echo e(status_label($alert["alert_status"])); ?>
                            </span>
                        </td>

                        <?php if ($canUpdateAlert): ?>
                            <td>
                                <form class="form-inline" method="POST" action="actions/update_alert_status.php">
                                    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                    <input type="hidden" name="alert_id" value="<?php echo e($alert["alert_id"]); ?>">

                                    <select name="alert_status">
                                        <option value="PENDIENTE" <?php echo $alert["alert_status"] === "PENDIENTE" ? "selected" : ""; ?>>
                                            Pendiente
                                        </option>
                                        <option value="NOTIFICADO_CLIENTE" <?php echo $alert["alert_status"] === "NOTIFICADO_CLIENTE" ? "selected" : ""; ?>>
                                            Notificado cliente
                                        </option>
                                        <option value="RESPONDIDO" <?php echo $alert["alert_status"] === "RESPONDIDO" ? "selected" : ""; ?>>
                                            Respondido
                                        </option>
                                        <option value="RESUELTO" <?php echo $alert["alert_status"] === "RESUELTO" ? "selected" : ""; ?>>
                                            Listo despacho
                                        </option>
                                        <option value="VENCIDO" <?php echo $alert["alert_status"] === "VENCIDO" ? "selected" : ""; ?>>
                                            Vencido
                                        </option>
                                    </select>

                                    <button class="btn btn-red" type="submit">Guardar</button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-title">
            <div>
                <h3>Resumen operativo por nivel de riesgo</h3>
                <p>Distribución visual de las alertas activas.</p>
            </div>
        </div>

        <?php
            $total = max(1, (int)($kpis["total_alertas"] ?? 0));
            $critPct = round((($kpis["criticas"] ?? 0) / $total) * 100);
            $medPct = round((($kpis["medias"] ?? 0) / $total) * 100);
            $resPct = round((($kpis["resueltas"] ?? 0) / $total) * 100);
        ?>

        <div class="bar-row">
            <div class="bar-label">
                <span>Críticas</span>
                <span><?php echo $critPct; ?>%</span>
            </div>
            <div class="bar-track">
                <div class="bar-fill" style="width: <?php echo $critPct; ?>%;"></div>
            </div>
        </div>

        <div class="bar-row">
            <div class="bar-label">
                <span>Medias</span>
                <span><?php echo $medPct; ?>%</span>
            </div>
            <div class="bar-track">
                <div class="bar-fill orange" style="width: <?php echo $medPct; ?>%;"></div>
            </div>
        </div>

        <div class="bar-row">
            <div class="bar-label">
                <span>Resueltas</span>
                <span><?php echo $resPct; ?>%</span>
            </div>
            <div class="bar-track">
                <div class="bar-fill green" style="width: <?php echo $resPct; ?>%;"></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-title">
            <div>
                <h3>Uso operativo</h3>
                <p>Esta vista es usada por el supervisor de almacén.</p>
            </div>
        </div>

        <p>
            El supervisor puede revisar qué pedidos están en riesgo, qué productos tienen déficit y qué pedidos ya fueron resueltos antes del despacho.
        </p>

        <p>
            Cuando se actualiza el estatus, el cambio se guarda directamente en la tabla <strong>risk_alerts</strong> y queda trazabilidad en <strong>audit_log</strong>.
        </p>
    </div>
</div>

<?php include "includes/footer.php"; ?>