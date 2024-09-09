<?php

require_once __DIR__ . '/../controller/db-connect.php';
require_once __DIR__ . '/../controller/login/loginController.php';
require_once __DIR__ . '/../controller/register/registerController.php';
require_once __DIR__ . '/../controller/addmod/addModController.php';
require_once __DIR__ . '/../controller/settings/settingsController.php';
require_once __DIR__ . '/../controller/editmod/editModController.php';
class PublicRoutes {
    private $app;
    private $database;
    private $pdo;
    private $loginController;
    private $registerController;
    private $addModsController;
    private $settingsController;
    private $editModController;
    public function __construct($app) {
      $this->app = $app;
      $this->database = new CrmConnect();
      $this->pdo = $this->database->db;
      $this->loginController = new LoginController($this->pdo);
      $this->registerController = new RegisterController($this->pdo);
      $this->addModsController = new AddModController($this->pdo);
      $this->settingsController = new SettingsController($this->pdo);
      $this->editModController = new EditModController($this->pdo);
    }

    public function loginRoutes() {
        $this->app->respond('GET', '/login', function () {
            if (isset($_SESSION['user_id'])) {
                header('Location: /dashboard');
                exit;
            }
            require_once __DIR__ . '/../pages/auth/Login.html';
        });  
    }

    public function registerRoutes() {
        $this->app->respond('GET', '/register', function () {
            if (isset($_SESSION['user_id'])) {
                header('Location: /dashboard');
                exit;
            }
            require_once __DIR__ . '/../pages/auth/Register.html';
        });
    }


    public function apiRoutes() {
        $this->app->respond('GET', '/api/mod/[a:id]', function ($request) {
            header('Content-Type: application/json');
            $modId = $request->id;
            $stmt = $this->pdo->prepare('SELECT * FROM `mod-list` WHERE mod_id = :mod_id AND verified = 1');
            $stmt->bindParam(':mod_id', $modId);
            $stmt->execute();
            $mod = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$mod) {
                return json_encode(array('success' => false, 'message' => 'Mod not found or not verified'));
            }
           
            $sanitizedMod = array(
                'success' => true,
                'mod' => array(
                    'mod_name' => htmlspecialchars($mod['mod_name'], ENT_QUOTES, 'UTF-8'),
                    'mod_logo' => htmlspecialchars($mod['mod_logo'], ENT_QUOTES, 'UTF-8'),
                    'mod_id' => htmlspecialchars($mod['mod_id'], ENT_QUOTES, 'UTF-8'),
                    'version' => htmlspecialchars($mod['version'], ENT_QUOTES, 'UTF-8'),
                    'author' => htmlspecialchars($mod['author'], ENT_QUOTES, 'UTF-8'),
                    'download_count' => htmlspecialchars($mod['download_count'], ENT_QUOTES, 'UTF-8'),
                    'tags' => $mod['tags'],
                    'sha256' => htmlspecialchars($mod['sha256'], ENT_QUOTES, 'UTF-8'),
                    'file_id' => htmlspecialchars($mod['file_id'], ENT_QUOTES, 'UTF-8'),
                    'md5' => htmlspecialchars($mod['md5'], ENT_QUOTES, 'UTF-8'),
                    'sha1' => htmlspecialchars($mod['sha1'], ENT_QUOTES, 'UTF-8'),
                    'size' => htmlspecialchars($mod['size'], ENT_QUOTES, 'UTF-8'),
                    'long_description' => nl2br(htmlspecialchars($mod['long_description'], ENT_QUOTES, 'UTF-8'))
                )
            );
            echo json_encode($sanitizedMod);
        });

        

        $this->app->respond('GET', '/api/mod/[a:id]/download', function ($request) {
            $modId = htmlspecialchars($request->id, ENT_QUOTES, 'UTF-8');
            $stmt = $this->pdo->prepare('SELECT * FROM `mod-list` WHERE mod_id = :mod_id AND verified = 1');
            $stmt->bindParam(':mod_id', $modId);
            $stmt->execute();
            $mod = $stmt->fetch(PDO::FETCH_ASSOC);
          
            if (!$mod) {
                header('Content-Type: application/json');
                echo json_encode(array('success' => false, 'message' => 'Mod not found or not verified'));
                exit;
            }

            $filePath = realpath(__DIR__ . "/../../jar-assets/{$modId}.jar");
            $baseDir = realpath(__DIR__ . '/../../jar-assets');

            if (strpos($filePath, $baseDir) !== 0 || !file_exists($filePath)) {
                header('Content-Type: application/json');
                echo json_encode(array('success' => false, 'message' => 'File not found'));
                exit;
            }

            $stmt = $this->pdo->prepare('UPDATE `mod-list` SET download_count = download_count + 1 WHERE mod_id = :mod_id');
            $stmt->bindParam(':mod_id', $modId);
            $stmt->execute();

            $modName = htmlspecialchars($mod['mod_name'], ENT_QUOTES, 'UTF-8');

            header('Content-Description: File Transfer');
            header('Content-Type: application/java-archive');
            header('Content-Disposition: attachment; filename="' . $modId . '.jar"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
        });

        $this->app->respond('GET', '/modlist', function () {
            require_once __DIR__ . '/../pages/modlist/ModList.html';
        });
    }

    // Routes that require no authentication
    public function publicRoutes() {
        $this->app->respond('GET', '/get-tags', function () {
            header('Content-Type: application/json');
            $stmt = $this->pdo->prepare('SELECT * FROM tags');
            $stmt->execute();
            $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $sanitizedTags = array_map(function($tag) {
                return array(
                    'name' => htmlspecialchars($tag['name'], ENT_QUOTES, 'UTF-8')
                );
            }, $tags);

            echo json_encode($sanitizedTags);
        });

        $this->app->respond('GET', '/get-mods', function () {
            header('Content-Type: application/json');

            $stmt = $this->pdo->prepare('SELECT * FROM `mod-list` WHERE verified = 1');
            $stmt->execute();
            $mods = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $sanitizedMods = array_map(function($mod) {
                return array(
                    'short_description' => htmlspecialchars($mod['short_description'], ENT_QUOTES, 'UTF-8'),
                    'mod_name' => htmlspecialchars($mod['mod_name'], ENT_QUOTES, 'UTF-8'),
                    'download_count' => htmlspecialchars($mod['download_count'], ENT_QUOTES, 'UTF-8'),
                    'author' => htmlspecialchars($mod['author'], ENT_QUOTES, 'UTF-8'),
                    'mod_id' => htmlspecialchars($mod['mod_id'], ENT_QUOTES, 'UTF-8'),
                    'version' => htmlspecialchars($mod['version'], ENT_QUOTES, 'UTF-8'),
                    'tags' => $mod['tags'],
                    'mod_logo' => htmlspecialchars($mod['mod_logo'], ENT_QUOTES, 'UTF-8')
                );
            }, $mods);

            echo json_encode($sanitizedMods);
        });

        

        $this->app->respond('GET', '/get-versions', function () {
            header('Content-Type: application/json');
            $stmt = $this->pdo->prepare('SELECT * FROM versions');
            $stmt->execute();
            $versions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $sanitizedVersions = array_map(function($version) {
                return array(
                    'name' => htmlspecialchars($version['name'], ENT_QUOTES, 'UTF-8')
                );
            }, $versions);
            echo json_encode($sanitizedVersions);
        });

        $this->app->respond('GET', '/mod/[a:id]', function ($request) {
            $modId = htmlspecialchars($request->id, ENT_QUOTES, 'UTF-8');
            $stmt = $this->pdo->prepare('SELECT * FROM `mod-list` WHERE mod_id = :mod_id AND verified = 1');
            $stmt->bindParam(':mod_id', $modId);
            $stmt->execute();
            $mod = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$mod) {
                return $this->app->response()->redirect('/')->send();
            }
            require_once __DIR__ . '/../pages/mods/ModPage.html';
        });

        $this->app->respond('POST', '/register-auth', function () {
            if (isset($_POST['email']) && isset($_POST['password'])) {
                $this->registerController->registerController($_POST['email'], $_POST['password']);
            } else {
                exit(json_encode(array('success' => false, 'message' => 'Invalid request')));
            }
        });

        $this->app->respond('POST', '/login-auth', function () {
            if (isset($_POST['email']) && isset($_POST['password'])) {
                $this->loginController->loginController($_POST['email'], $_POST['password']);
            } else {
                exit(json_encode(array('success' => false, 'message' => 'Invalid request')));
            }
        });



        $this->app->respond('GET', '/logout', function () {
            session_destroy();
            $this->app->response()->redirect('/login')->send();
        });

        $this->app->respond('GET', '/authed', function () {
            if (isset($_SESSION['user_id'])) {
                echo json_encode(array('success' => true, 'message' => 'User is authenticated'));
            } else {
                echo json_encode(array('success' => false, 'message' => 'User is not authenticated'));
            }
        });
    }

    // Routes that require authentication
    public function dashboardRoutes() {
       

        $this->app->respond('GET', '/dashboard', function () {
            if (!isset($_SESSION['user_id'])) {
                return $this->app->response()->redirect('/login')->send();
            }
            require_once __DIR__ . '/../pages/auth/dashboard/Dashboard.html';
        });

        $this->app->respond('GET', '/dashboard/add', function () {
            if (!isset($_SESSION['user_id'])) {
                return $this->app->response()->redirect('/login')->send();
            }
            require_once __DIR__ . '/../pages/auth/dashboard/addmods/AddMods.html';
        });

        $this->app->respond('POST', '/dashboard/mod/[a:id]/edit', function ($request) {
            if (!isset($_SESSION['user_id'])) {
                return $this->app->response()->redirect('/login')->send();
            }
            
            $this->editModController->editMod($request->id);
        });

        $this->app->respond('POST', '/dashboard/add-mod', function () {
            header('Content-Type: application/json');
            if (!isset($_SESSION['user_id'])) {
                return $this->app->response()->redirect('/login')->send();
            }
         
            if (isset($_POST['modName']) && isset($_POST['shortDescription']) && isset($_POST['longDescription']) && isset($_POST['versions']) && isset($_FILES['jarFile']) && isset($_FILES['logoFile']) && isset($_POST['tags'])) {
                $this->addModsController->addMod($_POST['modName'], $_POST['longDescription'], $_POST['shortDescription'], $_POST['versions'], $_FILES['jarFile'], $_FILES['logoFile'], $_POST['tags']);
            } else {
                $missingFields = [];
                if (!isset($_POST['modName'])) $missingFields[] = 'modName';
                if (!isset($_POST['shortDescription'])) $missingFields[] = 'shortDescription';
                if (!isset($_POST['longDescription'])) $missingFields[] = 'longDescription';
                if (!isset($_POST['versions'])) $missingFields[] = 'versions';
                if (!isset($_FILES['jarFile'])) $missingFields[] = 'jarFile';
                if (!isset($_FILES['logoFile'])) $missingFields[] = 'logoFile';
                if (!isset($_POST['tags'])) $missingFields[] = 'tags';
                exit(json_encode(array('success' => false, 'message' => 'Please fill in all the fields: ' . implode(', ', $missingFields))));
            }

        });

        $this->app->respond('POST', '/dashboard/settings/change-username', function () {
            header('Content-Type: application/json');
            if (!isset($_SESSION['user_id'])) {
                return $this->app->response()->redirect('/login')->send();
            }
            $input = json_decode(file_get_contents('php://input'), true);
            if (isset($input['newUsername'])) {
                $this->settingsController->changeUsername($input['newUsername']);
            } else {
                exit(json_encode(array('success' => false, 'message' => 'Invalid request')));
            }
        });

        $this->app->respond('POST', '/dashboard/settings/check-username', function () {
            header('Content-Type: application/json');
            if (!isset($_SESSION['user_id'])) {
                return $this->app->response()->redirect('/login')->send();
            }
            $input = json_decode(file_get_contents('php://input'), true);
            if (isset($input['username'])) {
                $this->settingsController->usernameExistsPull($input['username']);
            } else {
                exit(json_encode(array('success' => false, 'message' => 'Invalid request')));
            }
        });

        $this->app->respond('GET', '/dashboard/settings', function () {
            if (!isset($_SESSION['user_id'])) {
                return $this->app->response()->redirect('/login')->send();
            }
            require_once __DIR__ . '/../pages/auth/dashboard/settings/Settings.html';
        });

        $this->app->respond('GET', '/dashboard/settings/generate-api-key', function () {
            header('Content-Type: application/json');
            if (!isset($_SESSION['user_id'])) {
                return $this->app->response()->redirect('/login')->send();
            }
            $this->settingsController->generateApiKey();
        });

        $this->app->respond('GET', '/dashboard/settings/get-api-key', function () {
            header('Content-Type: application/json');
            if (!isset($_SESSION['user_id'])) {
                return $this->app->response()->redirect('/login')->send();
            }
            $this->settingsController->getApiKey();
        });




        $this->app->respond('GET', '/dashboard/your-mods', function () {
            header('Content-Type: application/json');
            if (!isset($_SESSION['user_id'])) {
                return json_encode(array('success' => false, 'message' => 'User is not authenticated'));
            }
            $userId = $_SESSION['user_id'];
            $stmt = $this->pdo->prepare('SELECT *, 
                (SELECT COUNT(*) FROM `mod-list` WHERE user_id = :user_id AND verified = 1) as verified_count, 
                (SELECT COUNT(*) FROM `mod-list` WHERE user_id = :user_id) as total_count 
                FROM `mod-list` WHERE user_id = :user_id');
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            $mods = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $verifiedCount = $mods[0]['verified_count'] ?? 0;
            $totalCount = $mods[0]['total_count'] ?? 0;
            $sanitizedMods = array_map(function($mod) {
                return array(
                    'short_description' => htmlspecialchars($mod['short_description'], ENT_QUOTES, 'UTF-8'),
                    'long_description' => htmlspecialchars($mod['long_description'], ENT_QUOTES, 'UTF-8'),
                    'mod_name' => htmlspecialchars($mod['mod_name'], ENT_QUOTES, 'UTF-8'),
                    'download_count' => htmlspecialchars($mod['download_count'], ENT_QUOTES, 'UTF-8'),
                    'author' => htmlspecialchars($mod['author'], ENT_QUOTES, 'UTF-8'),
                    'mod_id' => htmlspecialchars($mod['mod_id'], ENT_QUOTES, 'UTF-8'),
                    'version' => htmlspecialchars($mod['version'], ENT_QUOTES, 'UTF-8'),
                    'tags' => $mod['tags'],
                    'mod_logo' => htmlspecialchars($mod['mod_logo'], ENT_QUOTES, 'UTF-8'),
                    'verified' => htmlspecialchars($mod['verified'], ENT_QUOTES, 'UTF-8')
                );
            }, $mods);

            echo json_encode(array('success' => true, 'mods' => $sanitizedMods, 'verified_count' => $verifiedCount, 'total_count' => $totalCount));
        });
    }
}