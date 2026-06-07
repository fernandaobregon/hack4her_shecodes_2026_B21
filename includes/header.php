<?php
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/helpers.php";

require_login();

$user = current_user();
$pageTitle = $pageTitle ?? "Order Rescue";
$pageSubtitle = $pageSubtitle ?? "Módulo de prevención de sustituciones";
$activePage = $activePage ?? "";
$csrfToken = create_csrf_token();

function nav_item($permission, $href, $label, $activePage, $key) {
    if (!user_has_permission($permission)) {
        return "";
    }

    $activeClass = $activePage === $key ? "active" : "";

    return '<a class="nav-link '.$activeClass.'" href="'.BASE_URL.$href.'">'.$label.'</a>';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo e($pageTitle); ?> | Order Rescue</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
</head>

<body>

<header class="main-header">
    <div class="header-left">
        <div class="brand-card">
            <img src="<?php echo BASE_URL; ?>assets/img/arca_logo.png" alt="Arca Continental">
        </div>

        <div class="header-divider"></div>

        <div class="product-title">
            <h1>Order Rescue</h1>
            <p><?php echo e($pageSubtitle); ?></p>
        </div>
    </div>

    <div class="header-user">
        <strong><?php echo e($user["full_name"]); ?></strong>
        <span><?php echo e($user["role"]); ?></span>
        <a href="<?php echo BASE_URL; ?>logout.php">Cerrar sesión</a>
    </div>
</header>

<nav class="main-nav">
    <?php
        echo nav_item("view_cedis_dashboard", "dashboard_cedis.php", "Vista CEDIS", $activePage, "cedis");
        echo nav_item("view_logistics_dashboard", "dashboard_logistica.php", "Logística", $activePage, "logistica");
        echo nav_item("view_data_dashboard", "dashboard_datos.php", "Datos / ML", $activePage, "datos");
        echo nav_item("view_manager_dashboard", "dashboard_gerente.php", "Gerencia", $activePage, "gerente");
        echo nav_item("view_admin_dashboard", "dashboard_admin.php", "Admin", $activePage, "admin");
    ?>
</nav>

<main class="app-container">

<?php if (!empty($_GET["ok"])): ?>
    <div class="toast-success">
        Cambio guardado correctamente.
    </div>
<?php endif; ?>

<?php if (!empty($_GET["error"])): ?>
    <div class="toast-error">
        Ocurrió un error. Revisa la información e intenta de nuevo.
    </div>
<?php endif; ?>