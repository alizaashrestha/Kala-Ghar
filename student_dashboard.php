<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: login.php');
    exit();
}

// Fetch student's information
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

// Fetch available classes
$stmt = $conn->prepare("
    SELECT c.*, u.full_name as teacher_name, u.id as teacher_id 
    FROM classes c 
    JOIN users u ON c.teacher_id = u.id 
    WHERE c.available_seats > 0
    ORDER BY c.created_at DESC
");
$stmt->execute();
$classes = $stmt->fetchAll();

// Fetch student's booking requests
$stmt = $conn->prepare("
    SELECT 
        br.*,
        c.title as class_title,
        c.image_path,
        c.skill_type,
        c.skill_level,
        c.schedule,
        u.full_name as teacher_name,
        u.id as teacher_id
    FROM booking_requests br 
    JOIN classes c ON br.class_id = c.id 
    JOIN users u ON c.teacher_id = u.id 
    WHERE br.student_id = ? 
    ORDER BY br.created_at DESC
");

try {
    $stmt->execute([$_SESSION['user_id']]);
    $bookings = $stmt->fetchAll();
} catch(PDOException $e) {
    $bookings = [];
    $booking_error = "Unable to fetch your bookings at this time.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Kala-Ghar</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .dashboard {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        .profile-section {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .profile-info {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #8B5E57;
            margin-bottom: 1rem;
        }
        .edit-profile-button {
            display: inline-block;
            padding: 0.5rem 1rem;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
            margin-bottom: 1rem;
        }
        .edit-profile-button:hover {
            background-color: #5a6268;
        }
        .classes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
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
        .class-teacher {
            color: #666;
            margin-bottom: 1rem;
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
        .bookings-section {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .booking-card {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        .booking-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 1rem;
        }
        .booking-details {
            flex: 1;
        }
        .booking-status {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-accepted {
            background-color: #d4edda;
            color: #155724;
        }
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">Kala-Ghar</div>
            <div class="nav-links">
                <a href="index.php">Home</a>
                
                <a href="logout.php">Logout</a>
            </div>
        </nav>
    </header>

    <div class="dashboard">
        <div class="profile-section">
            <h2>Welcome, <?php echo htmlspecialchars($student['full_name']); ?>!</h2>
            <div class="profile-info">
                <?php if (!empty($student['profile_image']) && file_exists($student['profile_image'])): ?>
                    <img src="<?php echo htmlspecialchars($student['profile_image']); ?>" alt="Profile Picture" class="profile-picture">
                <?php endif; ?>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
                <p><strong>Location:</strong> <?php echo htmlspecialchars($student['location']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($student['phone_number']); ?></p>
                <?php if (!empty($student['skills'])): ?>
                    <p><strong>Interests:</strong> <?php echo htmlspecialchars($student['skills']); ?></p>
                <?php endif; ?>
                <a href="edit_profile.php" class="edit-profile-button">Edit Profile</a>
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
                        <p class="class-teacher">
                            Teacher: <a href="view_teacher_profile.php?id=<?php echo $class['teacher_id']; ?>"><?php echo htmlspecialchars($class['teacher_name']); ?></a>
                        </p>
                        <p class="class-details">
                            Skill: <?php echo htmlspecialchars($class['skill_type']); ?><br>
                            Level: <?php echo ucfirst(htmlspecialchars($class['skill_level'])); ?><br>
                            Duration: <?php echo htmlspecialchars($class['duration']); ?><br>
                            Schedule: <?php echo htmlspecialchars($class['schedule']); ?>
                        </p>
                        <p class="class-price">₹<?php echo number_format($class['price'], 2); ?></p>
                        <p>Available Seats: <?php echo $class['available_seats']; ?></p>
                        <a href="book_class.php?id=<?php echo $class['id']; ?>" class="book-button">Book Now</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <h2>Your Bookings</h2>
        <div class="bookings-section">
            <?php if (isset($booking_error)): ?>
                <p class="error-message"><?php echo htmlspecialchars($booking_error); ?></p>
            <?php elseif (empty($bookings)): ?>
                <p>You haven't made any bookings yet.</p>
            <?php else: ?>
                <?php foreach ($bookings as $booking): ?>
                    <div class="booking-card">
                        <?php if (!empty($booking['image_path']) && file_exists($booking['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($booking['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($booking['class_title'] ?? 'Class Image'); ?>" 
                                 class="booking-image">
                        <?php else: ?>
                            <img src="images/default-class.jpg" alt="Default class image" class="booking-image">
                        <?php endif; ?>
                        <div class="booking-details">
                            <h3><?php echo htmlspecialchars($booking['class_title'] ?? 'Untitled Class'); ?></h3>
                            <p>Teacher: 
                                <?php if (!empty($booking['teacher_id']) && !empty($booking['teacher_name'])): ?>
                                    <a href="view_teacher_profile.php?id=<?php echo htmlspecialchars($booking['teacher_id']); ?>">
                                        <?php echo htmlspecialchars($booking['teacher_name']); ?>
                                    </a>
                                <?php else: ?>
                                    <span>Unknown Teacher</span>
                                <?php endif; ?>
                            </p>
                            <p>Requested on: <?php echo date('F j, Y', strtotime($booking['created_at'])); ?></p>
                            <?php if (!empty($booking['skill_type'])): ?>
                                <p>Skill: <?php echo htmlspecialchars($booking['skill_type']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($booking['schedule'])): ?>
                                <p>Schedule: <?php echo htmlspecialchars($booking['schedule']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="booking-status status-<?php echo htmlspecialchars($booking['status']); ?>">
                            <?php echo ucfirst(htmlspecialchars($booking['status'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 