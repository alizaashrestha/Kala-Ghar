<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch user's current information
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $full_name = $_POST['full_name'] ?? '';
        $location = $_POST['location'] ?? '';
        $phone_number = $_POST['phone_number'] ?? '';
        $skills = $_POST['skills'] ?? '';

        // Handle profile picture upload
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_picture'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
            $max_size = 5 * 1024 * 1024; // 5MB

            if (!in_array($file['type'], $allowed_types)) {
                throw new Exception('Only JPG, JPEG, and PNG files are allowed.');
            }

            if ($file['size'] > $max_size) {
                throw new Exception('File size must be less than 5MB.');
            }

            // Create uploads directory if it doesn't exist
            $upload_dir = 'uploads/profile_pictures';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Generate unique filename
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid('profile_') . '.' . $file_extension;
            $filepath = $upload_dir . '/' . $filename;

            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Delete old profile picture if it exists
                if (!empty($user['profile_image']) && file_exists($user['profile_image'])) {
                    unlink($user['profile_image']);
                }

                // Update database with new profile picture path
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET full_name = ?, location = ?, phone_number = ?, skills = ?, profile_image = ?
                    WHERE id = ?
                ");
                $stmt->execute([$full_name, $location, $phone_number, $skills, $filepath, $_SESSION['user_id']]);
            } else {
                throw new Exception('Failed to upload file.');
            }
        } else {
            // Update without changing profile picture
            $stmt = $conn->prepare("
                UPDATE users 
                SET full_name = ?, location = ?, phone_number = ?, skills = ?
                WHERE id = ?
            ");
            $stmt->execute([$full_name, $location, $phone_number, $skills, $_SESSION['user_id']]);
        }

        $success_message = 'Profile updated successfully!';
        
        // Refresh user data
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Kala-Ghar</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .profile-form {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }
        .form-group input[type="text"],
        .form-group input[type="tel"],
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        .form-group textarea {
            height: 150px;
            resize: vertical;
        }
        .current-image {
            max-width: 200px;
            margin: 1rem 0;
            border-radius: 4px;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .submit-button {
            background-color: #8B5E57;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s;
        }
        .submit-button:hover {
            background-color: #6d4a44;
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
                <?php if ($_SESSION['user_type'] === 'teacher'): ?>
                    <a href="teacher_dashboard.php">Dashboard</a>
                <?php else: ?>
                    <a href="student_dashboard.php">Dashboard</a>
                <?php endif; ?>
                <a href="logout.php">Logout</a>
            </div>
        </nav>
    </header>

    <div class="profile-form">
        <h2>Edit Profile</h2>
        
        <?php if ($success_message): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="location">Location</label>
                <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="phone_number">Phone Number</label>
                <input type="tel" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="skills">
                    <?php if ($_SESSION['user_type'] === 'teacher'): ?>
                        Skills & Expertise
                    <?php else: ?>
                        Interests
                    <?php endif; ?>
                </label>
                <textarea id="skills" name="skills"><?php echo htmlspecialchars($user['skills'] ?? ''); ?></textarea>
                <?php if ($_SESSION['user_type'] === 'student'): ?>
                    <p><small>Tell us what types of arts and crafts interest you</small></p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="profile_picture">Profile Picture</label>
                <?php if (!empty($user['profile_image']) && file_exists($user['profile_image'])): ?>
                    <div>
                        <p>Current Profile Picture:</p>
                        <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Current Profile Picture" class="current-image">
                    </div>
                <?php endif; ?>
                <input type="file" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/jpg">
                <p><small>Allowed formats: JPG, JPEG, PNG. Maximum size: 5MB</small></p>
            </div>

            <button type="submit" class="submit-button">Update Profile</button>
        </form>
    </div>
</body>
</html> 