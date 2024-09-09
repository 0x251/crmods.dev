<?php
// NEED TO CLEAN UP THIS CODE :Skull 
class AddModController {
    private $db;
    public function __construct($db) {
        $this->db = $db;
    }
    public function addMod($mod_name, $mod_description, $short_description, $mod_version, $mod_file, $mod_image, $mod_tags) {
        if(isset($_SESSION['user_id'])) {
            if($mod_name && $mod_description && $short_description && $mod_version && $mod_file && $mod_image) {
                if (strlen($mod_description) > 3000) {
                    exit(json_encode(array('success' => false, 'message' => 'Long description cannot be over 3000 characters')));
                }
                if (strlen($short_description) > 60) {
                    exit(json_encode(array('success' => false, 'message' => 'Short description cannot be over 60 characters')));
                }
                
                $mod_description = $this->sanitizeAndEncode($mod_description);
                $short_description = $this->sanitizeAndEncode($short_description);
                $mod_name = $this->sanitizeAndEncode($mod_name);
                if ($this->isValidImage($mod_image) && $this->isValidJarFile($mod_file)) {
                    try {
                        $stmt = $this->db->prepare("SELECT COUNT(*) FROM `mod-list` WHERE mod_name = ?");
                        $stmt->execute([$mod_name]);
                        $mod_count = $stmt->fetchColumn();

                        if ($mod_count > 0) {
                            exit(json_encode(array('success' => false, 'message' => 'A mod with this name already exists')));
                        }

                        $stmt = $this->db->prepare("SELECT COUNT(*) FROM `versions` WHERE `name` = ?");
                        $stmt->execute([$mod_version]);
                        $version_count = $stmt->fetchColumn();

                        if ($version_count == 0) {
                            exit(json_encode(array('success' => false, 'message' => 'Invalid mod version')));
                        }

                        foreach ($mod_tags as $tag) {
                            $stmt = $this->db->prepare("SELECT COUNT(*) FROM `tags` WHERE `name` = ?");
                            $stmt->execute([$tag]);
                            $tag_count = $stmt->fetchColumn();

                            if ($tag_count == 0) {
                                exit(json_encode(array('success' => false, 'message' => 'Invalid tag: ' . $tag)));
                            }
                        }

                        $mod_image_url = $this->uploadImageToImgur($mod_image);
                        if($mod_image_url) {
                            $virus_total_data = $this->uploadJarToVirusTotal($mod_file);
                            if ($virus_total_data !== null && $virus_total_data['malicious'] < 30) {
                                $mod_id = uniqid() . uniqid() . uniqid();
                                
                                $jar_assets_dir = __DIR__ . '/../../../jar-assets/';
                                $jar_file_path = $jar_assets_dir . $mod_id . '.jar';
                                if (!move_uploaded_file($mod_file['tmp_name'], $jar_file_path)) {
                                    exit(json_encode(array('success' => false, 'message' => 'Failed to save jar file')));
                                }

                                $stmt = $this->db->prepare("INSERT INTO `mod-list` (author, user_id, mod_id, mod_name, long_description, short_description, `version`, jar, mod_logo, tags, verified, sha256, md5, sha1, size, malicious, file_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                                $stmt->execute([htmlspecialchars($_SESSION['username']), $_SESSION['user_id'], $mod_id, $mod_name, $mod_description, $short_description, $mod_version, $mod_file, $mod_image_url, json_encode($mod_tags), false, $virus_total_data['sha256'], $virus_total_data['md5'], $virus_total_data['sha1'], $virus_total_data['size'], $virus_total_data['malicious'], $virus_total_data['file_id']]);
                                exit(json_encode(array('success' => true, 'message' => 'Mod added successfully')));
                            } else {
                                exit(json_encode(array('success' => false, 'message' => 'Jar file failed VirusTotal scan Please contact support to add your mod')));
                            }
                        } else {
                            exit(json_encode(array('success' => false, 'message' => 'Failed to upload logo')));
                        }
                    } catch (PDOException $e) {
                        exit(json_encode(array('success' => false, 'message' => 'Database error: ' . $e->getMessage())));
                    }
                } else {
                    exit(json_encode(array('success' => false, 'message' => 'Invalid image or jar file')));
                }
            }
        }
        
    }

    private function isValidJarFile($file) {
        if (empty($file['tmp_name'])) {
            return false;
        }
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $file_mime = mime_content_type($file['tmp_name']);
        if (strtolower($file_extension) === 'jar' && $file_mime === 'application/java-archive') {
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
        $client_id = ''; // Replace with your Imgur client ID
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