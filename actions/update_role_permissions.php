<?php
require_once __DIR__ . "/../includes/auth.php";

require_login();
require_permission("manage_permissions");
validate_csrf();

$submittedPermissions = $_POST["permissions"] ?? [];

try {
    $pdo->beginTransaction();

    /*
        Obtener roles editables
    */
    $rolesStmt = $pdo->query("SELECT role_id, role_name FROM roles WHERE role_name <> 'CLIENTE'");
    $roles = $rolesStmt->fetchAll();

    /*
        Obtener todos los permisos válidos
    */
    $permissionsStmt = $pdo->query("SELECT permission_key FROM permissions");
    $permissions = $permissionsStmt->fetchAll(PDO::FETCH_COLUMN);

    /*
        Guardar estado anterior para auditoría
    */
    $oldStmt = $pdo->query("SELECT role_id, permission_key, is_allowed FROM role_permissions");
    $oldPermissions = $oldStmt->fetchAll();

    /*
        Para cada rol y cada permiso:
        - si el checkbox vino, se guarda 1
        - si no vino, se guarda 0
    */
    foreach ($roles as $role) {
        $roleId = $role["role_id"];
        $roleName = $role["role_name"];

        $allowedForRole = $submittedPermissions[$roleId] ?? [];

        foreach ($permissions as $permissionKey) {
            $isAllowed = in_array($permissionKey, $allowedForRole, true) ? 1 : 0;

            /*
                Protección crítica:
                ADMIN nunca debe perder acceso a Admin, settings y permissions.
            */
            if (
                $roleName === "ADMIN" &&
                in_array($permissionKey, [
                    "view_admin_dashboard",
                    "manage_settings",
                    "manage_permissions"
                ], true)
            ) {
                $isAllowed = 1;
            }

            $sql = "INSERT INTO role_permissions 
                    (role_id, permission_key, is_allowed)
                    VALUES
                    (:role_id, :permission_key, :is_allowed)
                    ON DUPLICATE KEY UPDATE
                        is_allowed = VALUES(is_allowed)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                "role_id" => $roleId,
                "permission_key" => $permissionKey,
                "is_allowed" => $isAllowed
            ]);
        }
    }

    /*
        Estado nuevo para auditoría
    */
    $newStmt = $pdo->query("SELECT role_id, permission_key, is_allowed FROM role_permissions");
    $newPermissions = $newStmt->fetchAll();

    log_audit(
        $pdo,
        $_SESSION["user_id"],
        "role_permissions",
        "MATRIX",
        "UPDATE_ROLE_PERMISSIONS",
        $oldPermissions,
        $newPermissions
    );

    $pdo->commit();

    header("Location: " . BASE_URL . "dashboard_admin.php?ok=1");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    header("Location: " . BASE_URL . "dashboard_admin.php?error=1");
    exit;
}
?>