<?php
require_once 'app/routes/indexRoutes.php';
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name("crm-mods");
    session_start();
    
}

$indexRoutes = new IndexRoutes();
$indexRoutes->run();