<?php

class EditModController {
    private $pdo;
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    public function editMod($modId) {
        $sql = "SELECT * FROM `mod-list` WHERE mod_id = :mod_id AND user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':mod_id', $modId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        $mod = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$mod) {
            exit(json_encode(array('success' => false, 'message' => 'Mod not found')));
        }

        $fields = [];
        $params = [':mod_id' => $modId];

        if (!empty($_POST['modName'])) {
            $fields[] = 'mod_name = :mod_name';
            $params[':mod_name'] = $this->sanitizeAndEncode($this->sanitizeDescription($_POST['modName']));
        }
        if (!empty($_POST['shortDescription'])) {
            $fields[] = 'short_description = :short_description';
            $params[':short_description'] = $this->sanitizeAndEncode($this->sanitizeDescription($_POST['shortDescription']));
        }
        if (!empty($_POST['longDescription'])) {
            $fields[] = 'long_description = :long_description';
            $params[':long_description'] = $this->sanitizeAndEncode($this->sanitizeDescription($_POST['longDescription']));
        }
        if (!empty($_POST['versions'])) {
            $fields[] = 'version = :version';
            $params[':version'] = $_POST['versions'];
        }
        if (!empty($_FILES['jarFile']['name'])) {
            $jarFile = $_FILES['jarFile'];
            if ($this->isValidJarFile($jarFile)) {
                $virusTotalData = $this->uploadJarToVirusTotal($jarFile);
                if ($virusTotalData !== null && $virusTotalData['malicious'] < 30) {
                    $fields[] = 'jar = :jar';
                    $params[':jar'] = $jarFile['name'];
                    $fields[] = 'sha256 = :sha256';
                    $params[':sha256'] = $virusTotalData['sha256'];
                    $fields[] = 'md5 = :md5';
                    $params[':md5'] = $virusTotalData['md5'];
                    $fields[] = 'sha1 = :sha1';
                    $params[':sha1'] = $virusTotalData['sha1'];
                    $fields[] = 'size = :size';
                    $params[':size'] = $virusTotalData['size'];
                    $fields[] = 'malicious = :malicious';
                    $params[':malicious'] = $virusTotalData['malicious'];
                    $fields[] = 'file_id = :file_id';
                    $params[':file_id'] = $virusTotalData['file_id'];

                    $jar_assets_dir = __DIR__ . '/../../../jar-assets/';
                    $jar_file_path = $jar_assets_dir . $modId . '.jar';
                    if (!move_uploaded_file($jarFile['tmp_name'], $jar_file_path)) {
                        exit(json_encode(array('success' => false, 'message' => 'Failed to save jar file')));
                    }
                } else {
                    exit(json_encode(array('success' => false, 'message' => 'Jar file failed VirusTotal scan. Please contact support to edit your mod')));
                }
            } else {
                exit(json_encode(array('success' => false, 'message' => 'Invalid jar file')));
            }
        }
        if (!empty($_FILES['logoFile']['name'])) {
            $logoFile = $_FILES['logoFile'];
            if ($this->isValidImage($logoFile)) {
                $logoUrl = $this->uploadImageToImgur($logoFile);
                if ($logoUrl) {
                    $fields[] = 'mod_logo = :mod_logo';
                    $params[':mod_logo'] = $logoUrl;
                } else {
                    exit(json_encode(array('success' => false, 'message' => 'Failed to upload logo')));
                }
            } else {
                exit(json_encode(array('success' => false, 'message' => 'Invalid image file')));
            }
        }
        if (!empty($_POST['tags'])) {
            $fields[] = 'tags = :tags';
            $params[':tags'] = $this->sanitizeAndEncode($_POST['tags']);
            
        }
        if (!empty($fields)) {
            try {
                $fields[] = 'verified = 0';
                $sql = 'UPDATE `mod-list` SET  ' . implode(', ', $fields) . ' WHERE mod_id = :mod_id AND user_id = :user_id';
                $params[':user_id'] = $_SESSION['user_id'];
                $stmt = $this->pdo->prepare($sql);
                foreach ($params as $key => &$val) {
                    $stmt->bindParam($key, $val);
                }
                if (!$stmt->execute()) {
                    exit(json_encode(array('success' => false, 'message' => 'Failed to update mod')));
                }
            } catch (PDOException $e) {
                exit(json_encode(array('success' => false, 'message' => 'Database error: ' . $e->getMessage())));
            }
        }

        exit(json_encode(array('success' => true, 'message' => 'Mod updated successfully')));
    }

    private function isValidJarFile($file) {
        
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $file_mime = $file['type'];
        if (strtolower($file_extension) === 'jar' && ($file_mime === 'application/java-archive' || $file_mime === 'application/x-java-archive' || $file_mime === 'application/octet-stream')) {
            return true;
        } else {
            return false;
        }
    }
    private function sanitizeDescription($description) {
        return preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $description);
    }

    private function sanitizeAndEncode($description) {
        $description = $this->sanitizeDescription($description);
        return htmlspecialchars($description, ENT_QUOTES, 'UTF-8');
    }
    
    private function isValidImage($image) {
        $allowed_extensions = ['jpg', 'jpeg', 'png'];
        $file_extension = pathinfo($image['name'], PATHINFO_EXTENSION);
        $check = getimagesize($image['tmp_name']);
        $allowed_mimes = ['image/jpeg', 'image/png'];
        $file_mime = $check['mime'];
        $file_size = $image['size'];
        $max_dimension = 320;
        return $check !== false && in_array(strtolower($file_extension), $allowed_extensions) && in_array($file_mime, $allowed_mimes) && $file_size > 0 && $file_size <= 10000000 && $check[0] <= $max_dimension && $check[1] <= $max_dimension;
    }

    private function uploadImageToImgur($image) {
        $client_id = '';
        $image_data = file_get_contents($image['tmp_name']);
        if ($image_data === false) {
            exit(json_encode(array('success' => false, 'message' => 'Failed to read image file')));
        }
        $base64_image = base64_encode($image_data);

        $options = [
            'http' => [
                'method' => 'POST',
                'header' => "Authorization: Client-ID $client_id\r\n" .
                            "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query(['image' => $base64_image])
            ]
        ];

        $context = stream_context_create($options);
        $response = file_get_contents('https://api.imgur.com/3/image', false, $context);
        if ($response === false) {
            exit(json_encode(array('success' => false, 'message' => 'Failed to upload image')));
        }
        $response_data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            exit(json_encode(array('success' => false, 'message' => 'Failed to decode response')));
        }
        if (!isset($response_data['data']['link'])) {
            exit(json_encode(array('success' => false, 'message' => 'Response does not contain image link')));
        }

        return $response_data['data']['link'];
    }
    private function uploadJarToVirusTotal($jar_file) {
        $api_key = ''; // Replace with your VirusTotal API key
        
        if ($jar_file['tmp_name'] === '') {
            exit(json_encode(array('success' => false, 'message' => 'Jar File is to big to be processed')));
        }
        $file_data = file_get_contents($jar_file['tmp_name']);

        if ($file_data === false) {
            exit(json_encode(array('success' => false, 'message' => 'Failed to read jar file')));
        }

        $boundary = uniqid();
        $delimiter = '-------------' . $boundary;
        $post_data = "--" . $delimiter . "\r\n" .
                     "Content-Disposition: form-data; name=\"file\"; filename=\"" . $jar_file['name'] . "\"\r\n" .
                     "Content-Type: application/java-archive\r\n\r\n" .
                     $file_data . "\r\n" .
                     "--" . $delimiter . "--\r\n";

        $options = [
            'http' => [
                'method' => 'POST',
                'header' => "x-apikey: $api_key\r\n" .
                            "Content-Type: multipart/form-data; boundary=" . $delimiter . "\r\n",
                'content' => $post_data
            ]
        ];

        $context = stream_context_create($options);
        $response = file_get_contents('https://www.virustotal.com/api/v3/files', false, $context);
        if ($response === false) {
            exit(json_encode(array('success' => false, 'message' => 'Failed to upload jar file')));
        }
        $response_data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            exit(json_encode(array('success' => false, 'message' => 'Failed to decode response')));
        }
        if (!isset($response_data['data']['id'])) {
            exit(json_encode(array('success' => false, 'message' => 'Response does not contain file ID')));
        }

        $file_id = $response_data['data']['id'];
       
        sleep(10);

        $options = [
            'http' => [
                'method' => 'GET',
                'header' => "x-apikey: $api_key\r\n"
            ]
        ];

        $context = stream_context_create($options);
        $response = @file_get_contents("https://www.virustotal.com/api/v3/analyses/$file_id", false, $context);
        if ($response === false) {
            exit(json_encode(array('success' => false, 'message' => 'Failed to get scan results')));
        }
        $response_data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            exit(json_encode(array('success' => false, 'message' => 'Failed to decode response')));
        }

        if (!isset($response_data['data']['attributes']['stats']['malicious'])) {
            exit(json_encode(array('success' => false, 'message' => 'Response does not contain analysis stats')));
        }
       
        $link = $response_data['data']['links']['item'];
        $file_id = basename($link);

        $data = [
            'malicious' => $response_data['data']['attributes']['stats']['malicious'],
            'sha256' => $response_data['meta']['file_info']['sha256'],
            'md5' => $response_data['meta']['file_info']['md5'],
            'sha1' => $response_data['meta']['file_info']['sha1'],
            'size' => $response_data['meta']['file_info']['size'],
            'file_id' => $file_id,
        ];

        return $data;
    }
}