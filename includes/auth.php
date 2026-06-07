<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/helpers.php";

/*
    IMPORTANTE:
    BASE_URL evita errores de rutas como:
    /actions/dashboard_admin.php

    Como tu proyecto está en:
    C:\xampp\htdocs\order_rescue_web

    La URL base es:
    http://localhost/order_rescue_web/
*/
define("BASE_URL", "/order_rescue_web/");

function require_login() {
    if (empty($_SESSION["user_id"])) {
        header("Location: " . BASE_URL . "login.php");
        exit;
    }
}

function current_user() {
    return [
        "user_id" => $_SESSION["user_id"] ?? null,
        "full_name" => $_SESSION["full_name"] ?? null,
        "email" => $_SESSION["email"] ?? null,
        "role_id" => $_SESSION["role_id"] ?? null,
        "role" => $_SESSION["role"] ?? null
    ];
}

function user_has_permission($permissionKey) {
    global $pdo;

    if (empty($_SESSION["role_id"])) {
        return false;
    }

    $sql = "SELECT COUNT(*) AS total
            FROM role_permissions
            WHERE role_id = :role_id
              AND permission_key = :permission_key
              AND is_allowed = 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        "role_id" => $_SESSION["role_id"],
        "permission_key" => $permissionKey
    ]);

    $row = $stmt->fetch();

    return $row && (int)$row["total"] > 0;
}

function require_permission($permissionKey) {
    if (!user_has_permission($permissionKey)) {
        die("No tienes permiso para realizar esta acción.");
    }
}

function redirect_by_role($roleName) {
    switch ($roleName) {
        case "SUPERVISOR_CEDIS":
            header("Location: " . BASE_URL . "dashboard_cedis.php");
            break;

        case "LOGISTICA":
            header("Location: " . BASE_URL . "dashboard_logistica.php");
            break;

        case "ANALISTA_DATOS":
            header("Location: " . BASE_URL . "dashboard_datos.php");
            break;

        case "GERENTE_CEDIS":
            header("Location: " . BASE_URL . "dashboard_gerente.php");
            break;

        case "ADMIN":
            header("Location: " . BASE_URL . "dashboard_admin.php");
            break;

        default:
            header("Location: " . BASE_URL . "login.php");
            break;
    }

    exit;
}
?>