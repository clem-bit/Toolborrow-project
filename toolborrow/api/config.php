<?php
define('DB_HOST',         'localhost');
define('DB_NAME',         'toolborrow');
define('DB_USER',         'root');
define('DB_PASS',         '');
define('JWT_SECRET',      'TbS3cr3t_2025_RGU_CMM007_Xp');
define('MAX_ACTIVE_LOANS', 3);

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}
?>
