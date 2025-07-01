<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
    header('Location: login.php');
    exit();
}

// Fetch teacher's information
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$teacher = $stmt->fetch();

// Fetch teacher's classes
$stmt = $conn->prepare("
    SELECT * FROM classes 
    WHERE teacher_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$classes = $stmt->fetchAll();

// Get booking requests for teacher's classes
$stmt = $conn->prepare("
    SELECT br.*, c.title as class_title, c.image_path, u.full_name as student_name, u.id as student_id
    FROM booking_requests br
    JOIN classes c ON br.class_id = c.id
    JOIN users u ON br.student_id = u.id
    WHERE c.teacher_id = ?
    ORDER BY br.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$booking_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - Kala-Ghar</title>
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
        .class-details {
            color: #666;
            margin-bottom: 1rem;
        }
        .class-price {
            font-weight: bold;
            color: #8B5E57;
            margin-bottom: 1rem;
        }
        .class-actions {
            display: flex;
            gap: 0.5rem;
        }
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        .btn-primary {
            background-color: #8B5E57;
            color: white;
        }
        .btn-primary:hover {
            background-color: #6d4a44;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        .requests-section {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .request-card {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        .request-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 1rem;
        }
        .request-details {
            flex: 1;
        }
        .request-status {
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
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">Kala-Ghar</div>
            <div class="nav-links">
                <a href="logout.php">Logout</a>
            </div>
        </nav>
    </header>

    <div class="dashboard">
        <div class="profile-section">
            <h2>Welcome, <?php echo htmlspecialchars($teacher['full_name']); ?>!</h2>
            <div class="profile-info">
                <?php if (!empty($teacher['profile_image']) && file_exists($teacher['profile_image'])): ?>
                    <img src="<?php echo htmlspecialchars($teacher['profile_image']); ?>" alt="Profile Picture" class="profile-picture">
                <?php endif; ?>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($teacher['email']); ?></p>
                <p><strong>Location:</strong> <?php echo htmlspecialchars($teacher['location']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($teacher['phone_number']); ?></p>
                <p><strong>Skills:</strong> <?php echo htmlspecialchars($teacher['skills']); ?></p>
                <a href="edit_profile.php" class="edit-profile-button">Edit Profile</a>
            </div>
            <a href="add_class.php" class="btn btn-primary">Add New Class</a>
        </div>

        <h2>Your Classes</h2>
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
                        <p>Available Seats: <?php echo $class['available_seats']; ?>/<?php echo $class['total_seats']; ?></p>
                        <div class="class-actions">
                            <a href="edit_class.php?id=<?php echo $class['id']; ?>" class="btn btn-secondary">Edit</a>
                            <a href="delete_class.php?id=<?php echo $class['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this class?')">Delete</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="dashboard-section">
            <h2 class="section-title">Booking Requests</h2>
            <?php if ($booking_requests): ?>
                <?php foreach ($booking_requests as $request): ?>
                    <div class="request-card">
                        <div class="request-header">
                            <h3><?php echo htmlspecialchars($request['class_title']); ?></h3>
                            <span class="status-badge status-<?php echo $request['status']; ?>">
                                <?php echo ucfirst($request['status']); ?>
                            </span>
                        </div>
                        <div class="request-details">
                            <p><strong>Student:</strong> 
                                <a href="view_student_profile.php?id=<?php echo $request['student_id']; ?>" class="student-link">
                                    <?php echo htmlspecialchars($request['student_name']); ?>
                                </a>
                            </p>
                            <p><strong>Requested On:</strong> <?php echo date('F j, Y', strtotime($request['created_at'])); ?></p>
                        </div>
                        <?php if ($request['status'] === 'pending'): ?>
                            <div class="request-actions">
                                <a href="accept_request.php?id=<?php echo $request['id']; ?>" class="btn btn-success">Accept</a>
                                <a href="reject_request.php?id=<?php echo $request['id']; ?>" class="btn btn-danger">Reject</a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No booking requests yet.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 