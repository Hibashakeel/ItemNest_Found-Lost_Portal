<?php
class Database {
    private $host = "localhost";
    private $user = "root";
    private $pass = "";
    private $dbname = "itemnest_db";
    protected $conn;

    public function connect() {
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname);
        if ($this->conn->connect_error) {
            die("Database Connection Failed: " . $this->conn->connect_error);
        }

        // ✅ Auto-create Admin if not exists
        $checkAdmin = $this->conn->query("SELECT * FROM users WHERE role='admin'");
        if ($checkAdmin->num_rows == 0) {
            $this->conn->query("
                INSERT INTO users (name, email, password, role)
                VALUES ('Admin', 'admin@itemnest.com', 'admin123', 'admin')
            ");
        }

        return $this->conn;
    }
}
?>