<?php
require_once "includes/auth.php";

require_login();
require_permission("view_manager_dashboard");

$pageTitle = "Dashboard Gerente";
$pageSubtitle = "Panel ejecutivo de sustituciones";
$activePage = "gerente";

$user = current_user();
$csrfToken = create_csrf_token();

$canNotifyPriority = user_has_permission("notify_priority_alerts");

/* =========================================================
   KPIs principales
   ========================================================= */

$sqlKpis = "SELECT
                tasa_sustitucion_general,
                alertas_criticas_activas,
                clientes_en_riesgo,
                sustituciones_historicas,
                lineas_analizadas
            FROM vw_manager_kpis
            LIMIT 1";

$kpis = $pdo->query($sqlKpis)->fetch();

if (!$kpis) {
    $kpis = [
        "tasa_sustitucion_general" => 0,
        "alertas_criticas_activas" => 0,
        "clientes_en_riesgo" => 0,
        "sustituciones_historicas" => 0,
        "lineas_analizadas" => 0
    ];
}

/* =========================================================
   Impacto económico estimado
   Se calcula usando pedidos relacionados a alertas críticas/medias activas.
   ========================================================= */

$sqlImpact = "
SELECT 
    COALESCE(SUM(x.total), 0) AS impacto_estimado
FROM (
    SELECT DISTINCT 
        o.order_pk,
        COALESCE(o.total, 0) AS total
    FROM vw_cedis_dashboard_alerts v
    LEFT JOIN orders o ON o.order_pk = v.order_pk
    WHERE v.risk_level IN ('CRITICO', 'MEDIO')
      AND v.alert_status IN ('PENDIENTE', 'NOTIFICADO_CLIENTE', 'RESPONDIDO')
) x
";

$impactRow = $pdo->query($sqlImpact)->fetch();
$impactoEstimado = $impactRow["impacto_estimado"] ?? 0;

/* =========================================================
   Top productos más sustituidos
   ========================================================= */

$sqlTopProducts = "SELECT 
                        producto_original,
                        total_sustituciones
                   FROM vw_productos_mas_sustituidos
                   LIMIT 10";

$topProducts = $pdo->query($sqlTopProducts)->fetchAll();

/* =========================================================
   CEDIS con más sustituciones
   ========================================================= */

$sqlTopCedis = "SELECT 
                    cedis_code,
                    total_sustituciones
                FROM vw_cedis_mas_sustituciones
                LIMIT 10";

$topCedis = $pdo->query($sqlTopCedis)->fetchAll();

/* =========================================================
   Reemplazos más usados
   ========================================================= */

$sqlReplacements = "SELECT 
                        producto_reemplazo,
                        total_usos_como_reemplazo
                    FROM vw_reemplazos_mas_usados
                    LIMIT 8";

$topReplacements = $pdo->query($sqlReplacements)->fetchAll();

/* =========================================================
   Clientes afectados / en riesgo
   ========================================================= */

$sqlAffectedClients = "
SELECT
    COALESCE(v.customer_id, 'SIN_CLIENTE') AS customer_id,
    COALESCE(v.customer_type, 'Cliente B2B') AS customer_type,
    COALESCE(v.customer_segment, 'SIN_SEGMENTO') AS customer_segment,
    COUNT(v.alert_id) AS total_alertas,
    SUM(CASE WHEN v.risk_level = 'CRITICO' THEN 1 ELSE 0 END) AS alertas_criticas,
    MAX(v.risk_score) AS max_risk_score,
    COALESCE(SUM(v.quantity), 0) AS productos_en_riesgo,
    COALESCE(SUM(DISTINCT o.total), 0) AS monto_estimado
FROM vw_cedis_dashboard_alerts v
LEFT JOIN orders o ON o.order_pk = v.order_pk
WHERE v.risk_level IN ('CRITICO', 'MEDIO')
GROUP BY 
    COALESCE(v.customer_id, 'SIN_CLIENTE'),
    COALESCE(v.customer_type, 'Cliente B2B'),
    COALESCE(v.customer_segment, 'SIN_SEGMENTO')
ORDER BY max_risk_score DESC, total_alertas DESC
LIMIT 12
";

$affectedClients = $pdo->query($sqlAffectedClients)->fetchAll();

/* =========================================================
   Alertas prioritarias que podrían notificarse
   ========================================================= */

$sqlPriorityAlerts = "
SELECT
    alert_id,
    pedido,
    customer_id,
    customer_type,
    producto_en_riesgo,
    risk_level,
    risk_score,
    alert_status
FROM vw_cedis_dashboard_alerts
WHERE risk_level = 'CRITICO'
  AND alert_status IN ('PENDIENTE', 'NOTIFICADO_CLIENTE')
ORDER BY risk_score DESC, created_at DESC
LIMIT 10
";

$priorityAlerts = $pdo->query($sqlPriorityAlerts)->fetchAll();

/* =========================================================
   Máximos para barras visuales
   ========================================================= */

$maxProductSubstitutions = 1;
foreach ($topProducts as $p) {
    $maxProductSubstitutions = max($maxProductSubstitutions, (int)$p["total_sustituciones"]);
}

$maxCedisSubstitutions = 1;
foreach ($topCedis as $c) {
    $maxCedisSubstitutions = max($maxCedisSubstitutions, (int)$c["total_sustituciones"]);
}

$maxReplacementUses = 1;
foreach ($topReplacements as $r) {
    $maxReplacementUses = max($maxReplacementUses, (int)$r["total_usos_como_reemplazo"]);
}

include "includes/header.php";
?>

<div class="page-title">
    <h2>Vista Gerente Regional</h2>
    <p>Monitoreo ejecutivo de sustituciones, impacto económico y clientes en riesgo.</p>
</div>

<div class="grid-4">
    <div class="kpi-card">
        <div class="kpi-label">Tasa de sustitución general</div>
        <div class="kpi-value red">
            <?php echo e($kpis["tasa_sustitucion_general"]); ?>%
        </div>
        <div class="kpi-note">
            Sustituciones históricas sobre líneas analizadas
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-label">Impacto estimado</div>
        <div class="kpi-value red">
            $<?php echo number_format((float)$impactoEstimado, 2); ?>
        </div>
        <div class="kpi-note">
            Monto asociado a pedidos en riesgo
        </div>
    </div>

    <div class="kpi-card orange">
        <div class="kpi-label">Clientes en riesgo</div>
        <div class="kpi-value orange">
            <?php echo e($kpis["clientes_en_riesgo"]); ?>
        </div>
        <div class="kpi-note">
            Clientes con alertas críticas o medias
        </div>
    </div>

    <div class="kpi-card green">
        <div class="kpi-label">Líneas analizadas</div>
        <div class="kpi-value">
            <?php echo number_format((int)$kpis["lineas_analizadas"]); ?>
        </div>
        <div class="kpi-note">
            Base operativa normalizada
        </div>
    </div>
</div>

<div class="grid-2">

    <!-- =====================================================
         PRODUCTOS MÁS SUSTITUIDOS
         ===================================================== -->

    <div class="card">
        <div class="card-title">
            <div>
                <h3>Top 10 productos más sustituidos</h3>
                <p>Productos que concentran la mayor cantidad de incidencias históricas.</p>
            </div>
        </div>

        <?php if (count($topProducts) === 0): ?>
            <p>No hay datos de sustituciones registrados.</p>
        <?php else: ?>
            <?php foreach ($topProducts as $product): ?>
                <?php
                    $percent = round(((int)$product["total_sustituciones"] / $maxProductSubstitutions) * 100);
                ?>

                <div class="bar-row">
                    <div class="bar-label">
                        <span><?php echo e($product["producto_original"]); ?></span>
                        <span><?php echo e($product["total_sustituciones"]); ?></span>
                    </div>
                    <div class="bar-track">
                        <div class="bar-fill" style="width: <?php echo $percent; ?>%;"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- =====================================================
         CEDIS CON MÁS SUSTITUCIONES
         ===================================================== -->

    <div class="card">
        <div class="card-title">
            <div>
                <h3>CEDIS con más sustituciones</h3>
                <p>Centros de distribución con mayor incidencia histórica.</p>
            </div>
        </div>

        <?php if (count($topCedis) === 0): ?>
            <p>No hay CEDIS relacionados con sustituciones confiables.</p>
        <?php else: ?>
            <?php foreach ($topCedis as $cedis): ?>
                <?php
                    $percent = round(((int)$cedis["total_sustituciones"] / $maxCedisSubstitutions) * 100);
                ?>

                <div class="bar-row">
                    <div class="bar-label">
                        <span>CEDIS <?php echo e($cedis["cedis_code"]); ?></span>
                        <span><?php echo e($cedis["total_sustituciones"]); ?></span>
                    </div>
                    <div class="bar-track">
                        <div class="bar-fill orange" style="width: <?php echo $percent; ?>%;"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div class="grid-2">

    <!-- =====================================================
         REEMPLAZOS MÁS USADOS
         ===================================================== -->

    <div class="card">
        <div class="card-title">
            <div>
                <h3>Reemplazos más usados</h3>
                <p>Productos utilizados con mayor frecuencia como sustitutos.</p>
            </div>
        </div>

        <?php if (count($topReplacements) === 0): ?>
            <p>No hay datos de reemplazos registrados.</p>
        <?php else: ?>
            <?php foreach ($topReplacements as $replacement): ?>
                <?php
                    $percent = round(((int)$replacement["total_usos_como_reemplazo"] / $maxReplacementUses) * 100);
                ?>

                <div class="bar-row">
                    <div class="bar-label">
                        <span><?php echo e($replacement["producto_reemplazo"]); ?></span>
                        <span><?php echo e($replacement["total_usos_como_reemplazo"]); ?></span>
                    </div>
                    <div class="bar-track">
                        <div class="bar-fill green" style="width: <?php echo $percent; ?>%;"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- =====================================================
         ALERTAS PRIORITARIAS
         ===================================================== -->

    <div class="card">
        <div class="card-title">
            <div>
                <h3>Alertas prioritarias</h3>
                <p>Pedidos críticos que requieren seguimiento ejecutivo o notificación a KAM.</p>
            </div>
        </div>

        <?php if (count($priorityAlerts) === 0): ?>
            <p>No hay alertas críticas pendientes de notificación.</p>
        <?php else: ?>
            <div class="priority-list">
                <?php foreach ($priorityAlerts as $alert): ?>
                    <div class="priority-item">
                        <div>
                            <strong>#<?php echo e($alert["alert_id"]); ?> · <?php echo e($alert["customer_type"]); ?></strong>
                            <p>
                                <?php echo e($alert["producto_en_riesgo"]); ?><br>
                                Pedido: <?php echo e($alert["pedido"] ?: "Sin pedido único"); ?>
                            </p>
                        </div>

                        <div class="priority-right">
                            <span class="badge <?php echo risk_badge_class($alert["risk_level"]); ?>">
                                <?php echo e($alert["risk_score"]); ?>%
                            </span>
                            <small><?php echo e($alert["alert_status"]); ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($canNotifyPriority): ?>
                <form method="POST" action="actions/notify_priority_alerts.php" style="margin-top: 18px;">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                    <button class="btn btn-red" type="submit">
                        Notificar alertas prioritarias a KAM
                    </button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- =====================================================
     CLIENTES AFECTADOS
     ===================================================== -->

<div class="card">
    <div class="card-title">
        <div>
            <h3>Clientes afectados o en riesgo</h3>
            <p>Clientes con mayor cantidad de alertas activas y riesgo operativo.</p>
        </div>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Tipo / segmento</th>
                    <th>Total alertas</th>
                    <th>Críticas</th>
                    <th>Productos en riesgo</th>
                    <th>Riesgo máximo</th>
                    <th>Monto estimado</th>
                </tr>
            </thead>

            <tbody>
                <?php if (count($affectedClients) === 0): ?>
                    <tr>
                        <td colspan="7">No hay clientes en riesgo registrados.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($affectedClients as $client): ?>
                    <?php
                        $riskScore = (int)$client["max_risk_score"];

                        if ($riskScore >= 70) {
                            $riskLevel = "CRITICO";
                        } elseif ($riskScore >= 30) {
                            $riskLevel = "MEDIO";
                        } else {
                            $riskLevel = "BAJO";
                        }
                    ?>

                    <tr>
                        <td class="table-id">
                            <?php echo e($client["customer_id"]); ?>
                        </td>

                        <td>
                            <strong><?php echo e($client["customer_type"]); ?></strong><br>
                            <small><?php echo e($client["customer_segment"]); ?></small>
                        </td>

                        <td>
                            <?php echo e($client["total_alertas"]); ?>
                        </td>

                        <td>
                            <?php echo e($client["alertas_criticas"]); ?>
                        </td>

                        <td>
                            <?php echo e($client["productos_en_riesgo"]); ?>
                        </td>

                        <td>
                            <span class="badge <?php echo risk_badge_class($riskLevel); ?>">
                                <?php echo e($riskLevel); ?> (<?php echo e($riskScore); ?>%)
                            </span>
                        </td>

                        <td>
                            <strong>$<?php echo number_format((float)$client["monto_estimado"], 2); ?></strong>
                        </td>
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
                <h3>Resumen ejecutivo</h3>
                <p>Indicadores clave para toma de decisiones.</p>
            </div>
        </div>

        <p>
            La tasa de sustitución permite medir qué proporción de líneas de pedido han sido afectadas por cambios de producto.
            Los CEDIS y productos con mayor incidencia ayudan a priorizar acciones correctivas.
        </p>

        <p>
            El impacto económico estimado agrupa el valor de pedidos asociados a alertas críticas o medias.
            Esto no representa pérdida final, sino monto operativo en riesgo.
        </p>
    </div>

    <div class="card">
        <div class="card-title">
            <div>
                <h3>Uso en Order Rescue</h3>
                <p>Esta vista conecta el análisis histórico con decisiones gerenciales.</p>
            </div>
        </div>

        <p>
            Gerencia puede identificar productos más afectados, CEDIS críticos y clientes que requieren atención prioritaria.
            También puede registrar notificaciones a KAM para dar seguimiento a cuentas sensibles.
        </p>

        <div class="model-warning">
            <strong>Nota:</strong>
            si el monto económico se ve muy alto o muy bajo, debe validarse con reglas financieras reales.
            En este prototipo se estima usando pedidos relacionados a alertas activas.
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>