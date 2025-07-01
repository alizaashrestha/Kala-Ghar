<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
    header('Location: login.php');
    exit();
}

// Get student ID from URL
$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$student_id) {
    header('Location: teacher_dashboard.php');
    exit();
}

// Get student information
$stmt = $conn->prepare("
    SELECT id, username, email, full_name, location, skills, phone_number, profile_image, created_at
    FROM users 
    WHERE id = ? AND user_type = 'student'
");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    header('Location: teacher_dashboard.php');
    exit();
}

// Get student's booking history
$stmt = $conn->prepare("
    SELECT br.*, c.title as class_title, c.skill_type, c.skill_level, c.schedule
    FROM booking_requests br
    JOIN classes c ON br.class_id = c.id
    WHERE br.student_id = ?
    ORDER BY br.created_at DESC
");
$stmt->execute([$student_id]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile - Kala-Ghar</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .profile-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .profile-header {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
        }
        .profile-info {
            flex: 1;
        }
        .profile-name {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: #333;
        }
        .profile-details {
            color: #666;
            margin-bottom: 0.5rem;
        }
        .profile-section {
            margin-bottom: 2rem;
        }
        .section-title {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #eee;
        }
        .booking-card {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .booking-title {
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }
        .booking-details {
            color: #666;
            font-size: 0.9rem;
        }
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
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
                <a href="teacher_dashboard.php">Dashboard</a>
            </div>
        </nav>
    </header>

    <div class="profile-container">
        <div class="profile-header">
            <img src="<?php echo $student['profile_image'] ? htmlspecialchars($student['profile_image']) : 'images/default-profile.jpg'; ?>" 
                 alt="Student Profile" class="profile-image">
            <div class="profile-info">
                <h1 class="profile-name"><?php echo htmlspecialchars($student['full_name']); ?></h1>
                <div class="profile-details">
                    <p><strong>Username:</strong> <?php echo htmlspecialchars($student['username']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
                    <p><strong>Location:</strong> <?php echo htmlspecialchars($student['location']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($student['phone_number']); ?></p>
                    <p><strong>Member Since:</strong> <?php echo date('F Y', strtotime($student['created_at'])); ?></p>
                </div>
            </div>
        </div>

        <div class="profile-section">
            <h2 class="section-title">Skills</h2>
            <p><?php echo nl2br(htmlspecialchars($student['skills'] ?? 'No skills listed')); ?></p>
        </div>

        <div class="profile-section">
            <h2 class="section-title">Booking History</h2>
            <?php if ($bookings): ?>
                <?php foreach ($bookings as $booking): ?>
                    <div class="booking-card">
                        <div class="booking-title"><?php echo htmlspecialchars($booking['class_title']); ?></div>
                        <div class="booking-details">
                            <p><strong>Skill Type:</strong> <?php echo htmlspecialchars($booking['skill_type']); ?></p>
                            <p><strong>Skill Level:</strong> <?php echo htmlspecialchars($booking['skill_level']); ?></p>
                            <p><strong>Schedule:</strong> <?php echo htmlspecialchars($booking['schedule']); ?></p>
                            <p><strong>Status:</strong> 
                                <span class="status-badge status-<?php echo $booking['status']; ?>">
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                            </p>
                            <p><strong>Booked On:</strong> <?php echo date('F j, Y', strtotime($booking['created_at'])); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No booking history available.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 