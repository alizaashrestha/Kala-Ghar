<?php
try {
    // Create database connection without selecting a database
    $conn = new PDO("mysql:host=localhost", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Database Setup Process:</h2>";
    
    // Read and execute the SQL file
    $sql = file_get_contents('database.sql');
    $conn->exec($sql);
    echo "✓ Database and tables created successfully!<br>";
    
    // Connect to the newly created database
    $conn = new PDO("mysql:host=localhost;dbname=kala_ghar", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if admin already exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $admin_email = 'admin@kalaghar.com';
    $stmt->execute([$admin_email, $admin_email]);
    
    if ($stmt->rowCount() > 0) {
        echo "✓ Admin account already exists!<br>";
    } else {
        // Create admin account
        $stmt = $conn->prepare("
            INSERT INTO users (username, email, password, user_type, full_name) 
            VALUES (?, ?, ?, 'admin', 'Administrator')
        ");
        
        $password = password_hash('Admin@123', PASSWORD_DEFAULT);
        $stmt->execute([$admin_email, $admin_email, $password]);
        echo "✓ Admin account created successfully!<br>";
    }
    
    // Verify tables
    $tables = ['users', 'classes', 'booking_requests'];
    echo "<h3>Verifying Database Structure:</h3>";
    echo "<ul>";
    foreach ($tables as $table) {
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<li>✓ Table '$table' exists</li>";
        } else {
            echo "<li>✗ Table '$table' is missing!</li>";
        }
    }
    echo "</ul>";
    
    echo "<h3>Admin Login Credentials:</h3>";
    echo "Username: admin@kalaghar.com<br>";
    echo "Password: Admin@123<br>";
    echo "<br>You can now go to <a href='login.php'>login page</a> and sign in with these credentials.";
    
} catch(PDOException $e) {
    echo "<h2>Error:</h2>";
    echo $e->getMessage();
}
?> 