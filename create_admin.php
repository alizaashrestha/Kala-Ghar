<?php
require_once 'config/database.php';

try {
    // Check if admin already exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $admin_email = 'admin@kalaghar.com';
    $stmt->execute([$admin_email, $admin_email]);
    
    if ($stmt->rowCount() > 0) {
        echo "Admin account already exists!";
    } else {
        // Create admin account
        $stmt = $conn->prepare("
            INSERT INTO users (username, email, password, user_type, full_name) 
            VALUES (?, ?, ?, 'admin', 'Administrator')
        ");
        
        $password = password_hash('Admin@123', PASSWORD_DEFAULT);
        
        $stmt->execute([$admin_email, $admin_email, $password]);
        echo "Admin account created successfully!<br>";
        echo "Username: admin@kalaghar.com<br>";
        echo "Password: Admin@123";
    }
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 