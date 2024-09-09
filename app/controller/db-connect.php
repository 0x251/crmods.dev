<?php

class CrmConnect{
    public $db;

    public function __construct(){
        try {
            $this->db = new PDO('mysql:host=localhost;dbname=crm', 'root', '');
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo 'CrmMods Connection Error: ' . $e->getMessage();
        }
    }
}