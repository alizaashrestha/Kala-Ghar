<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: student_dashboard.php');
    exit();
}

try {
    // Fetch teacher's information
    $stmt = $conn->prepare("
        SELECT * FROM users 
        WHERE id = ? AND user_type = 'teacher'
    ");
    $stmt->execute([$_GET['id']]);
    $teacher = $stmt->fetch();

    if (!$teacher) {
        throw new Exception("Teacher not found");
    }

    // Fetch teacher's classes
    $stmt = $conn->prepare("
        SELECT * FROM classes 
        WHERE teacher_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$_GET['id']]);
    $classes = $stmt->fetchAll();
} catch(Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Profile - Kala-Ghar</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .profile-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        .teacher-profile {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .teacher-header {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .teacher-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #8B5E57;
        }
        .teacher-info {
            flex: 1;
        }
        .teacher-name {
            font-size: 2rem;
            color: #333;
            margin-bottom: 0.5rem;
        }
        .teacher-details {
            color: #666;
            margin-bottom: 1rem;
        }
        .teacher-skills {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 4px;
            margin-top: 1rem;
        }
        .classes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        .class-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .class-card:hover {
            transform: translateY(-5px);
        }
        .class-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .class-content {
            padding: 1.5rem;
        }
        .class-title {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: #333;
        }
        .class-details {
            color: #666;
            margin-bottom: 1rem;
        }
        .class-price {
            font-weight: bold;
            color: #8B5E57;
            margin-bottom: 1rem;
        }
        .book-button {
            display: inline-block;
            padding: 0.5rem 1rem;
            background-color: #8B5E57;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .book-button:hover {
            background-color: #6d4a44;
        }
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
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
                <?php if ($_SESSION['user_type'] === 'student'): ?>
                    <a href="student_dashboard.php">Dashboard</a>
                <?php else: ?>
                    <a href="teacher_dashboard.php">Dashboard</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <div class="profile-container">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php else: ?>
            <div class="teacher-profile">
                <div class="teacher-header">
                    <img src="<?php echo $teacher['profile_image'] ?? 'images/default-avatar.jpg'; ?>" alt="<?php echo htmlspecialchars($teacher['full_name']); ?>" class="teacher-avatar">
                    <div class="teacher-info">
                        <h1 class="teacher-name"><?php echo htmlspecialchars($teacher['full_name']); ?></h1>
                        <div class="teacher-details">
                            <p><strong>Location:</strong> <?php echo htmlspecialchars($teacher['location']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($teacher['email']); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($teacher['phone_number']); ?></p>
                        </div>
                        <div class="teacher-skills">
                            <h3>Skills & Expertise</h3>
                            <p><?php echo htmlspecialchars($teacher['skills']); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <h2>Available Classes</h2>
            <div class="classes-grid">
                <?php foreach ($classes as $class): ?>
                    <div class="class-card">
                        <?php if ($class['image_path']): ?>
                            <img src="<?php echo htmlspecialchars($class['image_path']); ?>" alt="<?php echo htmlspecialchars($class['title']); ?>" class="class-image">
                        <?php else: ?>
                            <img src="images/default-class.jpg" alt="Default class image" class="class-image">
                        <?php endif; ?>
                        <div class="class-content">
                            <h3 class="class-title"><?php echo htmlspecialchars($class['title']); ?></h3>
                            <p class="class-details">
                                Skill: <?php echo htmlspecialchars($class['skill_type']); ?><br>
                                Level: <?php echo ucfirst(htmlspecialchars($class['skill_level'])); ?><br>
                                Duration: <?php echo htmlspecialchars($class['duration']); ?><br>
                                Schedule: <?php echo htmlspecialchars($class['schedule']); ?>
                            </p>
                            <p class="class-price">₹<?php echo number_format($class['price'], 2); ?></p>
                            <p>Available Seats: <?php echo $class['available_seats']; ?></p>
                            <?php if ($_SESSION['user_type'] === 'student' && $class['available_seats'] > 0): ?>
                                <a href="book_class.php?id=<?php echo $class['id']; ?>" class="book-button">Book Now</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <a href="student_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</body>
</html> 