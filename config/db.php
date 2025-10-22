<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "smartlms";

try {
    $conn = new mysqli($host, $user, $pass, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Koneksi database gagal: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("Maaf, terjadi kesalahan pada server. Silakan coba lagi nanti.");
}

// Fungsi helper untuk query aman
function safe_query($conn, $query, $params = [], $types = "") {
    try {
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare statement gagal: " . $conn->error);
        }
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt;
    } catch (Exception $e) {
        error_log("Query error: " . $e->getMessage());
        return false;
    }
}
?>
