<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Get user ID from URL
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$user_id) {
    header('Location: admin_dashboard.php');
    exit();
}

// Get user information
$stmt = $conn->prepare("
    SELECT id, username, email, full_name, location, skills, phone_number, profile_image, user_type, created_at
    FROM users 
    WHERE id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: admin_dashboard.php');
    exit();
}

// Get user's classes if they are a teacher
$classes = [];
if ($user['user_type'] === 'teacher') {
    $stmt = $conn->prepare("
        SELECT * FROM classes 
        WHERE teacher_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user_id]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get user's bookings if they are a student
$bookings = [];
if ($user['user_type'] === 'student') {
    $stmt = $conn->prepare("
        SELECT br.*, c.title as class_title, c.skill_type, c.skill_level, c.schedule,
               u.full_name as teacher_name
        FROM booking_requests br
        JOIN classes c ON br.class_id = c.id
        JOIN users u ON c.teacher_id = u.id
        WHERE br.student_id = ?
        ORDER BY br.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - Kala-Ghar</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .profile-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        .profile-header {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            display: flex;
            gap: 2rem;
        }
        .profile-image {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #8B5E57;
        }
        .profile-info {
            flex: 1;
        }
        .profile-name {
            font-size: 2rem;
            color: #333;
            margin-bottom: 1rem;
        }
        .profile-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        .profile-details p {
            margin: 0.5rem 0;
        }
        .profile-details strong {
            color: #666;
        }
        .section {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .section-title {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 1.5rem;
        }
        .classes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
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
        .booking-list {
            display: grid;
            gap: 1rem;
        }
        .booking-card {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-accepted {
            background: #d4edda;
            color: #155724;
        }
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }
        .back-button {
            display: inline-block;
            padding: 0.5rem 1rem;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .back-button:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">Kala-Ghar</div>
            <div class="nav-links">
                <a href="admin_dashboard.php">Dashboard</a>
                <a href="logout.php">Logout</a>
            </div>
        </nav>
    </header>

    <div class="profile-container">
        <a href="admin_dashboard.php" class="back-button">← Back to Dashboard</a>

        <div class="profile-header">
            <img src="<?php echo $user['profile_image'] ? htmlspecialchars($user['profile_image']) : 'images/default-profile.jpg'; ?>" 
                 alt="User Profile" class="profile-image">
            <div class="profile-info">
                <h1 class="profile-name"><?php echo htmlspecialchars($user['full_name']); ?></h1>
                <div class="profile-details">
                    <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><strong>Role:</strong> <?php echo ucfirst(htmlspecialchars($user['user_type'])); ?></p>
                    <p><strong>Location:</strong> <?php echo htmlspecialchars($user['location'] ?? 'Not specified'); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone_number'] ?? 'Not specified'); ?></p>
                    <p><strong>Member Since:</strong> <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                    <?php if ($user['user_type'] === 'teacher'): ?>
                        <p><strong>Skills:</strong> <?php echo htmlspecialchars($user['skills'] ?? 'Not specified'); ?></p>
                    <?php elseif ($user['user_type'] === 'student'): ?>
                        <p><strong>Interests:</strong> <?php echo htmlspecialchars($user['skills'] ?? 'Not specified'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($user['user_type'] === 'teacher' && !empty($classes)): ?>
            <div class="section">
                <h2 class="section-title">Classes</h2>
                <div class="classes-grid">
                    <?php foreach ($classes as $class): ?>
                        <div class="class-card">
                            <?php if ($class['image_path']): ?>
                                <img src="<?php echo htmlspecialchars($class['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($class['title']); ?>" 
                                     class="class-image">
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
                                <p>Available Seats: <?php echo $class['available_seats']; ?>/<?php echo $class['total_seats']; ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($user['user_type'] === 'student' && !empty($bookings)): ?>
            <div class="section">
                <h2 class="section-title">Booking History</h2>
                <div class="booking-list">
                    <?php foreach ($bookings as $booking): ?>
                        <div class="booking-card">
                            <div>
                                <h3><?php echo htmlspecialchars($booking['class_title']); ?></h3>
                                <p>Teacher: <?php echo htmlspecialchars($booking['teacher_name']); ?></p>
                                <p>Skill: <?php echo htmlspecialchars($booking['skill_type']); ?></p>
                                <p>Level: <?php echo ucfirst(htmlspecialchars($booking['skill_level'])); ?></p>
                                <p>Schedule: <?php echo htmlspecialchars($booking['schedule']); ?></p>
                                <p>Requested: <?php echo date('F j, Y', strtotime($booking['created_at'])); ?></p>
                            </div>
                            <span class="status-badge status-<?php echo $booking['status']; ?>">
                                <?php echo ucfirst($booking['status']); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 