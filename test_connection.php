<?php
echo "<h2>Server Information:</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";

echo "<h2>Testing Database Connection:</h2>";
try {
    $conn = new PDO("mysql:host=localhost;dbname=kala_ghar", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Database connection successful!<br>";
    
    // Test if we can query the database
    $stmt = $conn->query("SHOW TABLES");
    echo "<h3>Available Tables:</h3>";
    echo "<ul>";
    while ($row = $stmt->fetch()) {
        echo "<li>" . $row[0] . "</li>";
    }
    echo "</ul>";
    
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "<br>";
}

echo "<h2>Directory Permissions:</h2>";
$upload_dirs = [
    'uploads',
    'uploads/profile_pictures',
    'images'
];

foreach ($upload_dirs as $dir) {
    if (!file_exists($dir)) {
        echo "$dir directory does not exist<br>";
        // Try to create it
        if (mkdir($dir, 0777, true)) {
            echo "Created $dir directory successfully<br>";
        } else {
            echo "Failed to create $dir directory<br>";
        }
    } else {
        echo "$dir directory exists and is " . (is_writable($dir) ? "writable" : "not writable") . "<br>";
    }
}

echo "<h2>MySQL Status:</h2>";
try {
    $mysql_running = @fsockopen("localhost", 3306);
    if ($mysql_running) {
        echo "MySQL is running on port 3306<br>";
        fclose($mysql_running);
    } else {
        echo "MySQL is not running on port 3306<br>";
    }
} catch (Exception $e) {
    echo "Error checking MySQL: " . $e->getMessage() . "<br>";
}

echo "<h2>Apache Status:</h2>";
try {
    $apache_running = @fsockopen("localhost", 80);
    if ($apache_running) {
        echo "Apache is running on port 80<br>";
        fclose($apache_running);
    } else {
        echo "Apache is not running on port 80<br>";
    }
} catch (Exception $e) {
    echo "Error checking Apache: " . $e->getMessage() . "<br>";
}
?> 