<?php
require_once "includes/auth.php";

require_login();
require_permission("view_data_dashboard");

$pageTitle = "Dashboard Datos / Machine Learning";
$pageSubtitle = "Modelo predictivo y validación de inventario";
$activePage = "datos";

$user = current_user();
$csrfToken = create_csrf_token();

$canValidateInventory = user_has_permission("validate_inventory");
$canDeleteValidation = user_has_permission("delete_inventory_validation");

/* =========================================================
   Productos para formulario
   ========================================================= */

$sqlProducts = "SELECT product_id, product_name, product_hash, sku_code
                FROM products
                WHERE product_name IS NOT NULL
                ORDER BY product_name
                LIMIT 500";

$products = $pdo->query($sqlProducts)->fetchAll();

/* =========================================================
   CEDIS para formulario
   ========================================================= */

$sqlCedis = "SELECT cedis_code, country, region, city
             FROM cedis
             ORDER BY cedis_code";

$cedisList = $pdo->query($sqlCedis)->fetchAll();

/* =========================================================
   Último modelo ML activo
   ========================================================= */

$sqlModel = "SELECT 
                model_id,
                model_name,
                version,
                trained_at,
                accuracy,
                precision_score,
                recall_score,
                active
             FROM ml_model_versions
             WHERE active = 1
             ORDER BY trained_at DESC
             LIMIT 1";

$model = $pdo->query($sqlModel)->fetch();

if (!$model) {
    $model = [
        "model_name" => "Order Rescue Risk Model",
        "version" => "demo",
        "trained_at" => null,
        "accuracy" => 0,
        "precision_score" => 0,
        "recall_score" => 0,
        "active" => 0
    ];
}

/* =========================================================
   KPIs de validaciones
   ========================================================= */

$sqlValidationKpis = "SELECT
                        COUNT(*) AS total_validaciones,
                        SUM(CASE WHEN validation_status = 'CUADRA' THEN 1 ELSE 0 END) AS cuadran,
                        SUM(CASE WHEN validation_status = 'DIFERENCIA' THEN 1 ELSE 0 END) AS diferencias,
                        SUM(CASE WHEN validation_status = 'ERROR_CAPTURA' THEN 1 ELSE 0 END) AS errores
                      FROM inventory_validations";

$validationKpis = $pdo->query($sqlValidationKpis)->fetch();

if (!$validationKpis) {
    $validationKpis = [
        "total_validaciones" => 0,
        "cuadran" => 0,
        "diferencias" => 0,
        "errores" => 0
    ];
}

/* =========================================================
   Historial de validaciones
   ========================================================= */

$sqlValidations = "SELECT
                    validation_id,
                    product_name,
                    cedis_code,
                    sap_stock,
                    physical_stock,
                    difference_stock,
                    validation_status,
                    validated_by_name,
                    notes,
                    validated_at
                  FROM vw_data_validation_dashboard
                  LIMIT 30";

$validations = $pdo->query($sqlValidations)->fetchAll();

/* =========================================================
   Errores de calidad de datos
   ========================================================= */

$sqlIssues = "SELECT
                issue_type,
                severity,
                COUNT(*) AS total
              FROM data_quality_issues
              GROUP BY issue_type, severity
              ORDER BY 
                CASE severity
                    WHEN 'CRITICA' THEN 1
                    WHEN 'ALTA' THEN 2
                    WHEN 'MEDIA' THEN 3
                    WHEN 'BAJA' THEN 4
                    ELSE 5
                END,
                total DESC
              LIMIT 10";

$dataIssues = $pdo->query($sqlIssues)->fetchAll();

include "includes/header.php";
?>

<div class="page-title">
    <h2>Vista Equipo de Datos / Analista ML</h2>
    <p>Validación de inventario, calidad de datos y monitoreo del modelo predictivo.</p>
</div>

<div class="grid-4">
    <div class="kpi-card">
        <div class="kpi-label">Validaciones</div>
        <div class="kpi-value red">
            <?php echo e($validationKpis["total_validaciones"] ?? 0); ?>
        </div>
        <div class="kpi-note">Registros SAP vs físico</div>
    </div>

    <div class="kpi-card green">
        <div class="kpi-label">Inventarios que cuadran</div>
        <div class="kpi-value">
            <?php echo e($validationKpis["cuadran"] ?? 0); ?>
        </div>
        <div class="kpi-note">Sin diferencia detectada</div>
    </div>

    <div class="kpi-card orange">
        <div class="kpi-label">Diferencias</div>
        <div class="kpi-value orange">
            <?php echo e($validationKpis["diferencias"] ?? 0); ?>
        </div>
        <div class="kpi-note">SAP vs conteo físico</div>
    </div>

    <div class="kpi-card">
        <div class="kpi-label">Accuracy ML</div>
        <div class="kpi-value red">
            <?php echo e($model["accuracy"]); ?>%
        </div>
        <div class="kpi-note">Modelo activo: <?php echo e($model["version"]); ?></div>
    </div>
</div>

<div class="grid-2">

    <!-- =====================================================
         FORMULARIO SAP VS FÍSICO
         ===================================================== -->

    <div class="card">
        <div class="card-title">
            <div>
                <h3>Formulario de validación de carga</h3>
                <p>Compara inventario del sistema SAP contra conteo físico real del CEDIS.</p>
            </div>
        </div>

        <?php if ($canValidateInventory): ?>
            <form method="POST" action="actions/save_inventory_validation.php">
                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label>Producto a validar</label>
                        <select name="product_id" required>
                            <option value="">Selecciona un producto</option>

                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo e($product["product_id"]); ?>">
                                    <?php echo e($product["product_name"]); ?>
                                    <?php if (!empty($product["sku_code"])): ?>
                                        - SKU <?php echo e($product["sku_code"]); ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Ubicación almacén / CEDIS</label>
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
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Stock teórico sistema SAP</label>
                        <input type="number" name="sap_stock" min="0" placeholder="Ej. 17" required>
                    </div>

                    <div class="form-group">
                        <label>Stock físico real encontrado</label>
                        <input type="number" name="physical_stock" min="0" placeholder="Ej. 8" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Notas de validación</label>
                    <textarea name="notes" rows="3" placeholder="Ej. Diferencia encontrada durante conteo físico del CEDIS."></textarea>
                </div>

                <button class="btn btn-dark" type="submit">
                    Validar e inyectar datos al modelo
                </button>
            </form>
        <?php else: ?>
            <div class="status-pill">
                No tienes permiso para validar inventario.
            </div>
        <?php endif; ?>
    </div>

    <!-- =====================================================
         MÉTRICAS ML
         ===================================================== -->

    <div class="card">
        <div class="card-title">
            <div>
                <h3>Métricas del modelo de Machine Learning</h3>
                <p>Monitoreo del desempeño predictivo del modelo activo.</p>
            </div>
        </div>

        <div class="model-box">
            <div class="model-label">
                Modelo activo
            </div>

            <h2>
                <?php echo e($model["model_name"]); ?>
            </h2>

            <p>
                Versión: <strong><?php echo e($model["version"]); ?></strong>
                <?php if (!empty($model["trained_at"])): ?>
                    | Entrenado: <strong><?php echo e($model["trained_at"]); ?></strong>
                <?php endif; ?>
            </p>
        </div>

        <?php
            $accuracy = (float)($model["accuracy"] ?? 0);
            $precision = (float)($model["precision_score"] ?? 0);
            $recall = (float)($model["recall_score"] ?? 0);
        ?>

        <div class="metric-block">
            <div class="bar-row">
                <div class="bar-label">
                    <span>Accuracy</span>
                    <span><?php echo e($accuracy); ?>%</span>
                </div>
                <div class="bar-track">
                    <div class="bar-fill green" style="width: <?php echo min(100, max(0, $accuracy)); ?>%;"></div>
                </div>
            </div>

            <div class="bar-row">
                <div class="bar-label">
                    <span>Precision</span>
                    <span><?php echo e($precision); ?>%</span>
                </div>
                <div class="bar-track">
                    <div class="bar-fill" style="width: <?php echo min(100, max(0, $precision)); ?>%;"></div>
                </div>
            </div>

            <div class="bar-row">
                <div class="bar-label">
                    <span>Recall</span>
                    <span><?php echo e($recall); ?>%</span>
                </div>
                <div class="bar-track">
                    <div class="bar-fill orange" style="width: <?php echo min(100, max(0, $recall)); ?>%;"></div>
                </div>
            </div>
        </div>

        <div class="model-warning">
            <strong>Nota técnica:</strong>
            estas métricas representan el prototipo del modelo. En producción deberían recalcularse
            con inventario real por corte y datos directos desde SAP/app.
        </div>
    </div>
</div>

<!-- =====================================================
     HISTORIAL VALIDACIONES
     ===================================================== -->

<div class="card">
    <div class="card-title">
        <div>
            <h3>Inconsistencias y validaciones detectadas</h3>
            <p>Historial de diferencias entre stock SAP y conteo físico real.</p>
        </div>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Producto</th>
                    <th>CEDIS</th>
                    <th>Stock SAP</th>
                    <th>Stock físico</th>
                    <th>Diferencia</th>
                    <th>Estatus</th>
                    <th>Validado por</th>
                    <th>Fecha</th>

                    <?php if ($canDeleteValidation): ?>
                        <th>Eliminar</th>
                    <?php endif; ?>
                </tr>
            </thead>

            <tbody>
                <?php if (count($validations) === 0): ?>
                    <tr>
                        <td colspan="<?php echo $canDeleteValidation ? 10 : 9; ?>">
                            Aún no hay validaciones capturadas.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($validations as $validation): ?>
                    <?php
                        $status = $validation["validation_status"];

                        if ($status === "CUADRA") {
                            $badgeClass = "badge-low";
                        } elseif ($status === "DIFERENCIA") {
                            $badgeClass = "badge-medium";
                        } else {
                            $badgeClass = "badge-critical";
                        }
                    ?>

                    <tr>
                        <td class="table-id">
                            #<?php echo e($validation["validation_id"]); ?>
                        </td>

                        <td>
                            <?php echo e($validation["product_name"]); ?>
                            <?php if (!empty($validation["notes"])): ?>
                                <br><small><?php echo e($validation["notes"]); ?></small>
                            <?php endif; ?>
                        </td>

                        <td>
                            CEDIS <?php echo e($validation["cedis_code"]); ?>
                        </td>

                        <td>
                            <?php echo e($validation["sap_stock"]); ?>
                        </td>

                        <td>
                            <?php echo e($validation["physical_stock"]); ?>
                        </td>

                        <td>
                            <strong><?php echo e($validation["difference_stock"]); ?></strong>
                        </td>

                        <td>
                            <span class="badge <?php echo $badgeClass; ?>">
                                <?php echo e($status); ?>
                            </span>
                        </td>

                        <td>
                            <?php echo e($validation["validated_by_name"] ?: "Sistema"); ?>
                        </td>

                        <td>
                            <?php echo e($validation["validated_at"]); ?>
                        </td>

                        <?php if ($canDeleteValidation): ?>
                            <td>
                                <form method="POST" action="actions/delete_inventory_validation.php" onsubmit="return confirm('¿Seguro que deseas eliminar esta validación?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                    <input type="hidden" name="validation_id" value="<?php echo e($validation["validation_id"]); ?>">

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

<div class="grid-2">

    <!-- =====================================================
         CALIDAD DE DATOS
         ===================================================== -->

    <div class="card">
        <div class="card-title">
            <div>
                <h3>Resumen de calidad de datos</h3>
                <p>Problemas detectados durante la normalización y validación.</p>
            </div>
        </div>

        <?php if (count($dataIssues) === 0): ?>
            <p>No hay errores de calidad registrados.</p>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Tipo de issue</th>
                            <th>Severidad</th>
                            <th>Total</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($dataIssues as $issue): ?>
                            <tr>
                                <td>
                                    <?php echo e($issue["issue_type"]); ?>
                                </td>

                                <td>
                                    <?php
                                        $sev = $issue["severity"];

                                        if ($sev === "CRITICA" || $sev === "ALTA") {
                                            $sevClass = "badge-critical";
                                        } elseif ($sev === "MEDIA") {
                                            $sevClass = "badge-medium";
                                        } else {
                                            $sevClass = "badge-low";
                                        }
                                    ?>

                                    <span class="badge <?php echo $sevClass; ?>">
                                        <?php echo e($sev); ?>
                                    </span>
                                </td>

                                <td>
                                    <strong><?php echo e($issue["total"]); ?></strong>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- =====================================================
         EXPLICACIÓN
         ===================================================== -->

    <div class="card">
        <div class="card-title">
            <div>
                <h3>¿Por qué esta vista importa?</h3>
                <p>Datos alimenta la confiabilidad de todo Order Rescue.</p>
            </div>
        </div>

        <p>
            El equipo de datos valida que la información del sistema coincida con el stock físico real.
            Cuando existe una diferencia, la plataforma registra la inconsistencia y la puede usar como
            insumo para mejorar el modelo predictivo.
        </p>

        <p>
            Esta vista evita que el modelo de Machine Learning aprenda con datos incorrectos.
            Sin datos confiables, el Risk Score no sería preciso.
        </p>

        <div class="model-warning">
            <strong>Uso real:</strong>
            cada validación queda guardada en MySQL, genera trazabilidad y puede consultarse después
            por logística, CEDIS o gerencia.
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>
