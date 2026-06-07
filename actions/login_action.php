<?php
session_start();

require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/helpers.php";

$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

if ($email === "" || $password === "") {
    header("Location: ../login.php?error=1");
    exit;
}

$sql = "SELECT 
            u.user_id,
            u.full_name,
            u.email,
            u.password_hash,
            r.role_id,
            r.role_name
        FROM users u
        JOIN user_roles ur ON ur.user_id = u.user_id
        JOIN roles r ON r.role_id = ur.role_id
        WHERE u.email = :email
          AND u.active = 1
        LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute(["email" => $email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user["password_hash"])) {
    header("Location: ../login.php?error=1");
    exit;
}

$_SESSION["user_id"] = $user["user_id"];
$_SESSION["full_name"] = $user["full_name"];
$_SESSION["email"] = $user["email"];
$_SESSION["role_id"] = $user["role_id"];
$_SESSION["role"] = $user["role_name"];

require_once __DIR__ . "/../includes/auth.php";
redirect_by_role($user["role_name"]);
?>