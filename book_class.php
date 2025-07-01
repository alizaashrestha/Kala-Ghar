<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: login.php');
    exit();
}

$success = $error = '';

if (isset($_GET['id'])) {
    try {
        // Check if class exists and has available seats
        $stmt = $conn->prepare("
            SELECT * FROM classes 
            WHERE id = ? AND available_seats > 0
        ");
        $stmt->execute([$_GET['id']]);
        $class = $stmt->fetch();

        if (!$class) {
            throw new Exception("Class not found or no seats available");
        }

        // Check if student already has a pending or accepted request for this class
        $stmt = $conn->prepare("
            SELECT * FROM booking_requests 
            WHERE class_id = ? AND student_id = ? AND status IN ('pending', 'accepted')
        ");
        $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
        if ($existing_request = $stmt->fetch()) {
            if ($existing_request['status'] === 'pending') {
                throw new Exception("You already have a pending request for this class");
            } else {
                throw new Exception("You are already enrolled in this class");
            }
        }

        // Create booking request
        $stmt = $conn->prepare("
            INSERT INTO booking_requests (class_id, student_id, status) 
            VALUES (?, ?, 'pending')
        ");
        $stmt->execute([$_GET['id'], $_SESSION['user_id']]);

        $success = "Booking request submitted successfully!";
    } catch(Exception $e) {
        $error = $e->getMessage();
    }
} else {
    header('Location: student_dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Class - Kala-Ghar</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">Kala-Ghar</div>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="about.php">About</a>
                <a href="courses.php">Courses</a>
                <a href="contact.php">Contact</a>
            </div>
        </nav>
    </header>

    <div class="container">
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <a href="student_dashboard.php" class="btn btn-primary">Back to Dashboard</a>
    </div>
</body>
</html> 