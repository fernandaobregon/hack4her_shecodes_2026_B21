<?php
require_once "includes/auth.php";

require_login();
require_permission("view_admin_dashboard");

$pageTitle = "Configuración / Administrador";
$pageSubtitle = "Ajustes globales del sistema";
$activePage = "admin";

$user = current_user();
$csrfToken = create_csrf_token();

$canManageSettings = user_has_permission("manage_settings");
$canManagePermissions = user_has_permission("manage_permissions");

/* =========================================================
   Cargar configuraciones actuales
   ========================================================= */

$sqlSettings = "SELECT setting_key, setting_value, setting_label
                FROM system_settings
                ORDER BY setting_key";

$stmtSettings = $pdo->query($sqlSettings);
$settingsRows = $stmtSettings->fetchAll();

$settings = [];

foreach ($settingsRows as $row) {
    $settings[$row["setting_key"]] = $row;
}

/* Valores por default por si algún setting no existe */
function setting_value($settings, $key, $default = "") {
    return isset($settings[$key]) ? $settings[$key]["setting_value"] : $default;
}

/* =========================================================
   Cargar roles y permisos para matriz
   ========================================================= */

$sqlRoles = "SELECT role_id, role_name
             FROM roles
             WHERE role_name <> 'CLIENTE'
             ORDER BY role_id";

$roles = $pdo->query($sqlRoles)->fetchAll();

$sqlPermissions = "SELECT permission_key, permission_label, module
                   FROM permissions
                   ORDER BY module, permission_key";

$permissions = $pdo->query($sqlPermissions)->fetchAll();

$sqlAllowed = "SELECT role_id, permission_key, is_allowed
               FROM role_permissions";

$allowedRows = $pdo->query($sqlAllowed)->fetchAll();

$allowedMap = [];

foreach ($allowedRows as $row) {
    $allowedMap[$row["role_id"]][$row["permission_key"]] = (int)$row["is_allowed"];
}

/* =========================================================
   KPIs para mostrar estado del admin
   ========================================================= */

$totalRoles = count($roles);
$totalPermissions = count($permissions);

$sqlUsers = "SELECT COUNT(*) AS total FROM users WHERE active = 1";
$totalUsers = $pdo->query($sqlUsers)->fetch()["total"] ?? 0;

$sqlSettingsCount = "SELECT COUNT(*) AS total FROM system_settings";
$totalSettings = $pdo->query($sqlSettingsCount)->fetch()["total"] ?? 0;

include "includes/header.php";
?>

<div class="page-title">
    <h2>Configuración / Administrador</h2>
    <p>Define reglas de negocio, horarios límite y permisos de acceso por rol.</p>
</div>

<div class="grid-4">
    <div class="kpi-card">
        <div class="kpi-label">Usuarios activos</div>
        <div class="kpi-value red"><?php echo e($totalUsers); ?></div>
        <div class="kpi-note">Usuarios con acceso al sistema</div>
    </div>

    <div class="kpi-card">
        <div class="kpi-label">Roles operativos</div>
        <div class="kpi-value"><?php echo e($totalRoles); ?></div>
        <div class="kpi-note">Perfiles internos configurados</div>
    </div>

    <div class="kpi-card orange">
        <div class="kpi-label">Permisos</div>
        <div class="kpi-value orange"><?php echo e($totalPermissions); ?></div>
        <div class="kpi-note">Acciones controladas por rol</div>
    </div>

    <div class="kpi-card green">
        <div class="kpi-label">Reglas activas</div>
        <div class="kpi-value"><?php echo e($totalSettings); ?></div>
        <div class="kpi-note">Configuraciones del sistema</div>
    </div>
</div>

<div class="grid-2">

    <!-- =====================================================
         BLOQUE 1: HORARIOS LÍMITE / SLA
         ===================================================== -->

    <div class="card">
        <div class="card-title">
            <div>
                <h3>Definir horarios límite (SLA)</h3>
                <p>Controla los horarios críticos del flujo operativo.</p>
            </div>
        </div>

        <form method="POST" action="actions/save_system_settings.php">
            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">

            <div class="form-group">
                <label>Hora límite de respuesta del cliente B2B</label>
                <input 
                    type="time" 
                    name="client_response_deadline" 
                    value="<?php echo e(setting_value($settings, 'client_response_deadline', '09:00:00')); ?>"
                    <?php echo !$canManageSettings ? "disabled" : ""; ?>
                    required
                >
                <small class="help-text">
                    Hora máxima para que el cliente acepte, cambie o rechace una sustitución.
                </small>
            </div>

            <div class="form-group">
                <label>Hora sugerida de corte de inventario</label>
                <input 
                    type="time" 
                    name="inventory_cut_time" 
                    value="<?php echo e(setting_value($settings, 'inventory_cut_time', '18:30:00')); ?>"
                    <?php echo !$canManageSettings ? "disabled" : ""; ?>
                    required
                >
                <small class="help-text">
                    Momento en que logística valida el stock real del CEDIS.
                </small>
            </div>

            <div class="form-group">
                <label>Hora de notificación automática vía app</label>
                <input 
                    type="time" 
                    name="notification_time" 
                    value="<?php echo e(setting_value($settings, 'notification_time', '18:45:00')); ?>"
                    <?php echo !$canManageSettings ? "disabled" : ""; ?>
                    required
                >
                <small class="help-text">
                    Momento en que Order Rescue dispara alertas al cliente.
                </small>
            </div>

            <?php if ($canManageSettings): ?>
                <button class="btn btn-dark" type="submit">
                    Guardar ventanas de tiempo
                </button>
            <?php else: ?>
                <div class="status-pill">
                    Solo lectura
                </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- =====================================================
         BLOQUE 2: REGLAS DE NEGOCIO
         ===================================================== -->

    <div class="card">
        <div class="card-title">
            <div>
                <h3>Configuración de reglas de negocio</h3>
                <p>Define cómo se comporta el algoritmo ante riesgos operativos.</p>
            </div>
        </div>

        <form method="POST" action="actions/save_system_settings.php">
            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">

            <div class="form-group">
                <label>Umbral de riesgo para auto-sustitución (%)</label>
                <input 
                    type="number" 
                    name="auto_substitution_if_risk_over" 
                    min="0"
                    max="100"
                    value="<?php echo e(setting_value($settings, 'auto_substitution_if_risk_over', '90')); ?>"
                    <?php echo !$canManageSettings ? "disabled" : ""; ?>
                    required
                >
                <small class="help-text">
                    Si el riesgo supera este porcentaje, el sistema puede sugerir sustitución automática según política.
                </small>
            </div>

            <div class="form-group">
                <label>Margen mínimo de tolerancia en diferencia de SKU / cajas</label>
                <input 
                    type="number" 
                    name="sku_tolerance_margin" 
                    min="0"
                    value="<?php echo e(setting_value($settings, 'sku_tolerance_margin', '2')); ?>"
                    <?php echo !$canManageSettings ? "disabled" : ""; ?>
                    required
                >
                <small class="help-text">
                    Diferencias menores a este margen pueden ignorarse para evitar alertas innecesarias.
                </small>
            </div>

            <div class="form-group checkbox-line">
                <?php
                    $autoNotifyValue = setting_value($settings, 'enable_auto_notifications', '1');
                    $prioritizeVipValue = setting_value($settings, 'prioritize_vip_clients', '1');
                ?>

                <label>
                    <input 
                        type="checkbox" 
                        name="enable_auto_notifications" 
                        value="1"
                        <?php echo $autoNotifyValue == "1" ? "checked" : ""; ?>
                        <?php echo !$canManageSettings ? "disabled" : ""; ?>
                    >
                    Activar notificaciones automáticas al cliente
                </label>

                <label>
                    <input 
                        type="checkbox" 
                        name="prioritize_vip_clients" 
                        value="1"
                        <?php echo $prioritizeVipValue == "1" ? "checked" : ""; ?>
                        <?php echo !$canManageSettings ? "disabled" : ""; ?>
                    >
                    Priorizar clientes VIP o de alto impacto
                </label>
            </div>

            <?php if ($canManageSettings): ?>
                <button class="btn btn-red" type="submit">
                    Aplicar reglas al algoritmo
                </button>
            <?php else: ?>
                <div class="status-pill">
                    Solo lectura
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- =====================================================
     BLOQUE 3: MATRIZ DE PERMISOS
     ===================================================== -->

<div class="card">
    <div class="card-title">
        <div>
            <h3>Asignar permisos por rol de usuario</h3>
            <p>Controla qué puede ver, modificar, eliminar o guardar cada perfil del sistema.</p>
        </div>
    </div>

    <form method="POST" action="actions/update_role_permissions.php">
        <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Permiso / módulo</th>

                        <?php foreach ($roles as $role): ?>
                            <th>
                                <?php echo e($role["role_name"]); ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>

                <tbody>
                    <?php
                        $currentModule = null;
                    ?>

                    <?php foreach ($permissions as $permission): ?>
                        <?php if ($currentModule !== $permission["module"]): ?>
                            <?php $currentModule = $permission["module"]; ?>
                            <tr>
                                <td colspan="<?php echo count($roles) + 1; ?>" class="module-row">
                                    <?php echo e($currentModule); ?>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <tr>
                            <td>
                                <strong><?php echo e($permission["permission_label"]); ?></strong><br>
                                <small><?php echo e($permission["permission_key"]); ?></small>
                            </td>

                            <?php foreach ($roles as $role): ?>
                                <?php
                                    $roleId = $role["role_id"];
                                    $permissionKey = $permission["permission_key"];
                                    $isAllowed = $allowedMap[$roleId][$permissionKey] ?? 0;

                                    /*
                                        Seguridad:
                                        El ADMIN no puede perder permisos críticos,
                                        para evitar que se bloquee a sí mismo.
                                    */
                                    $lockedAdminPermission = (
                                        $role["role_name"] === "ADMIN" &&
                                        in_array($permissionKey, [
                                            "view_admin_dashboard",
                                            "manage_settings",
                                            "manage_permissions"
                                        ], true)
                                    );
                                ?>

                                <td>
                                    <input 
                                        type="checkbox"
                                        name="permissions[<?php echo e($roleId); ?>][]"
                                        value="<?php echo e($permissionKey); ?>"
                                        <?php echo $isAllowed ? "checked" : ""; ?>
                                        <?php echo (!$canManagePermissions || $lockedAdminPermission) ? "disabled" : ""; ?>
                                    >

                                    <?php if ($lockedAdminPermission): ?>
                                        <input 
                                            type="hidden"
                                            name="permissions[<?php echo e($roleId); ?>][]"
                                            value="<?php echo e($permissionKey); ?>"
                                        >
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($canManagePermissions): ?>
            <button class="btn btn-dark" type="submit" style="margin-top: 18px;">
                Actualizar matriz de seguridad
            </button>
        <?php else: ?>
            <div class="status-pill" style="margin-top: 18px;">
                No tienes permiso para editar la matriz
            </div>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <div class="card-title">
        <div>
            <h3>¿Por qué esta vista es importante?</h3>
            <p>Admin controla las reglas que hacen que Order Rescue funcione de forma real.</p>
        </div>
    </div>

    <p>
        Esta pantalla permite modificar horarios límite, reglas del algoritmo y permisos por rol. 
        Los cambios se guardan directamente en MySQL y afectan el comportamiento de los dashboards operativos.
    </p>

    <p>
        Por ejemplo: si se cambia la hora límite de respuesta del cliente a las 9:00 a.m., 
        esa regla puede ser utilizada por el módulo de alertas para marcar pedidos como vencidos o pendientes.
    </p>
</div>

<?php include "includes/footer.php"; ?>