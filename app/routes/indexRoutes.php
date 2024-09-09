<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../routes/publicRoutes.php';



class IndexRoutes extends \Klein\Klein {
    private $publicRoutes;
    public function __construct() {
        parent::__construct();
        // Initialize Class constructor with Klein instance GAYYYYY
        $this->publicRoutes = new PublicRoutes($this);
    }

    public function run() {
        // Landing page route
        $this->respond('GET', '/', function () {
            require_once __DIR__ . '/../pages/landing/HomeLanding.html';
        });

        // Sync routes with public routes, AKA login, register, etc.

        $this->publicRoutes->loginRoutes();
        $this->publicRoutes->registerRoutes();
        $this->publicRoutes->publicRoutes();
        // Some routes are public but require authentication
        $this->publicRoutes->apiRoutes();
        // Dashboard routes Require Auth
        $this->publicRoutes->dashboardRoutes();
  
        $this->dispatch();
    }
}
