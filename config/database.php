<?php
// Configuración de la base de datos local
define('DB_HOST', 'localhost');
define('DB_NAME', 'actividades_connect');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configuración de la base de datos de InfinityFree
/*define('DB_HOST', 'sql206.infinityfree.com');
define('DB_NAME', 'if0_40641398_actividades_connect');
define('DB_USER', 'if0_40641398');
define('DB_PASS', '2205130188Aa');*/

class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                                $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            echo "Error de conexión: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}
?>
