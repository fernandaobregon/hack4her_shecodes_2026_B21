<?php
session_start();

if (!empty($_SESSION["user_id"])) {
    require_once "includes/auth.php";
    redirect_by_role($_SESSION["role"]);
}

$error = $_GET["error"] ?? "";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Order Rescue | Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">

<div class="login-card">
    <div class="brand-box">
        <div class="arca-logo">Arca Continental</div>
        <h1>Order Rescue</h1>
        <p>Módulo de prevención de sustituciones</p>
    </div>

    <?php if ($error): ?>
        <div class="alert-error">
            Credenciales incorrectas. Intenta de nuevo.
        </div>
    <?php endif; ?>

    <form method="POST" action="actions/login_action.php">
        <label>Email</label>
        <input type="email" name="email" required placeholder="admin@orderrescue.local">

        <label>Contraseña</label>
        <input type="password" name="password" required placeholder="Order123!">

        <button type="submit">Iniciar sesión</button>
    </form>

    <div class="demo-users">
        <p><strong>Usuarios demo:</strong></p>
        <small>supervisor@orderrescue.local</small>
        <small>logistica@orderrescue.local</small>
        <small>datos@orderrescue.local</small>
        <small>gerente@orderrescue.local</small>
        <small>admin@orderrescue.local</small>
        <p><small>Contraseña: Order123!</small></p>
    </div>
</div>

</body>
</html>