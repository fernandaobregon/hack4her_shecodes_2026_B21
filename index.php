<?php
require_once "includes/auth.php";

if (empty($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

redirect_by_role($_SESSION["role"]);
?>