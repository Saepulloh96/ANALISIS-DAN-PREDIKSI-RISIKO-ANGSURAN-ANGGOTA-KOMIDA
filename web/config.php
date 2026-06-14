<?php
// Database Configuration and Automatic Setup/Initialization for Koperasi Mitra Dhuafa Risk App

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db_komida_risk');

/**
 * Connects to database and automatically initializes it if it does not exist
 */
function getDB() {
    $dsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        // First, connect without database to check/create database
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        // Check if database exists
        $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'");
        $dbExists = $stmt->fetch();

        if (!$dbExists) {
            // Create database
            $pdo->exec("CREATE DATABASE `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            // Reconnect selecting the database
            $pdo = new PDO($dsn . ";dbname=" . DB_NAME, DB_USER, DB_PASS, $options);
            
            // Load and execute database.sql
            $sqlFile = __DIR__ . '/database.sql';
            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                $pdo->exec($sql);
            }
            
            // Automatically import dataset from CSV to bootstrap database
            $csvFile = dirname(__DIR__) . '/data/dataset_komida.csv';
            if (file_exists($csvFile)) {
                $fp = fopen($csvFile, 'r');
                $headers = fgetcsv($fp);
                
                $sqlInsert = "INSERT INTO anggota (id_anggota, nama, tujuan_pinjaman, jumlah_pinjaman, tenor, cicilan, simp_wajib, simp_sukarela, simp_pensiun, simp_hari_raya, week_past_due, label_risiko) 
                              VALUES (:id_anggota, :nama, :tujuan_pinjaman, :jumlah_pinjaman, :tenor, :cicilan, :simp_wajib, :simp_sukarela, :simp_pensiun, :simp_hari_raya, :week_past_due, :label_risiko)
                              ON DUPLICATE KEY UPDATE label_risiko = VALUES(label_risiko)";
                $stmtInsert = $pdo->prepare($sqlInsert);
                
                $pdo->beginTransaction();
                while (($row = fgetcsv($fp)) !== FALSE) {
                    $record = array_combine($headers, $row);
                    $stmtInsert->execute([
                        ':id_anggota'      => $record['id_anggota'],
                        ':nama'            => $record['nama'],
                        ':tujuan_pinjaman' => $record['tujuan_pinjaman'],
                        ':jumlah_pinjaman' => (float)$record['jumlah_pinjaman'],
                        ':tenor'           => (int)$record['tenor'],
                        ':cicilan'         => (float)$record['cicilan'],
                        ':simp_wajib'      => (float)$record['simp_wajib'],
                        ':simp_sukarela'   => (float)$record['simp_sukarela'],
                        ':simp_pensiun'    => (float)$record['simp_pensiun'],
                        ':simp_hari_raya'  => (float)$record['simp_hari_raya'],
                        ':week_past_due'   => (int)$record['week_past_due'],
                        ':label_risiko'    => $record['label_risiko']
                    ]);
                }
                $pdo->commit();
                fclose($fp);
            }
        } else {
            // Just select the database
            $pdo = new PDO($dsn . ";dbname=" . DB_NAME, DB_USER, DB_PASS, $options);
        }
        
        return $pdo;
    } catch (PDOException $e) {
        // If connection fails (e.g. MySQL not running)
        // Throw exception but handle gracefully in UI
        throw new Exception("Koneksi Database Gagal: " . $e->getMessage());
    }
}
?>
