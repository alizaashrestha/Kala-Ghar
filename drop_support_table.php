<?php
require_once 'config/database.php';

try {
    $sql = "DROP TABLE IF EXISTS support_requests";
    $conn->exec($sql);
    echo "Support requests table dropped successfully";
} catch(PDOException $e) {
    echo "Error dropping table: " . $e->getMessage();
}
?> 