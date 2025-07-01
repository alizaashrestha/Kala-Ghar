<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_type = $_SESSION['user_type'];
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Kala-Ghar</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .dashboard {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .welcome-message {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .logout-btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            background-color: #e74c3c;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 1rem;
        }
        .logout-btn:hover {
            background-color: #c0392b;
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

    <div class="dashboard">
        <div class="welcome-message">
            <h2>Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
            <p>You are logged in as a <?php echo htmlspecialchars($user_type); ?>.</p>
            <?php if ($user_type === 'teacher'): ?>
                <p>You can start creating your courses and sharing your skills with students.</p>
            <?php else: ?>
                <p>You can start browsing available courses and learning new skills.</p>
            <?php endif; ?>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
</body>
</html> 