<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
    header('Location: login.php');
    exit();
}

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate input
        if (empty($_POST['title']) || empty($_POST['skill_type']) || empty($_POST['total_seats'])) {
            throw new Exception("Please fill in all required fields");
        }

        if (!is_numeric($_POST['total_seats']) || $_POST['total_seats'] < 1) {
            throw new Exception("Total seats must be a positive number");
        }

        if (!is_numeric($_POST['price']) || $_POST['price'] < 0) {
            throw new Exception("Price must be a non-negative number");
        }

        // Validate image upload
        if (!isset($_FILES['craft_image']) || $_FILES['craft_image']['error'] === UPLOAD_ERR_NO_FILE) {
            throw new Exception("Please upload an image of your craft");
        }

        // Handle image upload
        $image_path = null;
        if ($_FILES['craft_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/classes/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = pathinfo($_FILES['craft_image']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid() . '.' . $file_extension;
            $target_path = $upload_dir . $file_name;

            // Validate file type
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array(strtolower($file_extension), $allowed_types)) {
                throw new Exception("Only JPG, JPEG, PNG & GIF files are allowed");
            }

            // Validate file size (max 5MB)
            if ($_FILES['craft_image']['size'] > 5 * 1024 * 1024) {
                throw new Exception("File size must be less than 5MB");
            }

            if (move_uploaded_file($_FILES['craft_image']['tmp_name'], $target_path)) {
                $image_path = $target_path;
            } else {
                throw new Exception("Failed to upload image");
            }
        } else {
            throw new Exception("Error uploading image. Please try again.");
        }

        // Insert new class
        $stmt = $conn->prepare("
            INSERT INTO classes (
                teacher_id, title, description, skill_type, price, 
                duration, schedule, total_seats, available_seats, skill_level, image_path
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $_SESSION['user_id'],
            $_POST['title'],
            $_POST['description'],
            $_POST['skill_type'],
            $_POST['price'],
            $_POST['duration'],
            $_POST['schedule'],
            $_POST['total_seats'],
            $_POST['total_seats'], // Initially available seats equals total seats
            $_POST['skill_level'],
            $image_path
        ]);

        $success = "Class added successfully!";
        header("refresh:2;url=teacher_dashboard.php");
    } catch(Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Class - Kala-Ghar</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .required::after {
            content: " *";
            color: red;
        }
        .preview-image {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
            display: none;
        }
        .image-upload-info {
            color: #666;
            font-size: 0.9rem;
            margin-top: 0.5rem;
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

    <div class="form-container">
        <h2>Add New Class</h2>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label class="required" for="title">Class Title</label>
                <input type="text" id="title" name="title" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4"></textarea>
            </div>

            <div class="form-group">
                <label class="required" for="craft_image">Upload Craft Image</label>
                <input type="file" id="craft_image" name="craft_image" accept="image/*" onchange="previewImage(this)" required>
                <img id="imagePreview" class="preview-image" src="#" alt="Preview">
                <p class="image-upload-info">
                    * Required. Please upload a clear image of your craft.<br>
                    * Supported formats: JPG, JPEG, PNG, GIF<br>
                    * Maximum file size: 5MB
                </p>
            </div>

            <div class="form-group">
                <label class="required" for="skill_type">Skill Type</label>
                <input type="text" id="skill_type" name="skill_type" required 
                       placeholder="e.g., Pottery, Painting, Knitting">
            </div>

            <div class="form-group">
                <label class="required" for="price">Price (₹)</label>
                <input type="number" id="price" name="price" min="0" step="0.01" required>
            </div>

            <div class="form-group">
                <label for="duration">Duration</label>
                <input type="text" id="duration" name="duration" 
                       placeholder="e.g., 2 hours per session">
            </div>

            <div class="form-group">
                <label for="schedule">Schedule</label>
                <input type="text" id="schedule" name="schedule" 
                       placeholder="e.g., Every Monday and Wednesday, 2-4 PM">
            </div>

            <div class="form-group">
                <label class="required" for="total_seats">Total Seats</label>
                <input type="number" id="total_seats" name="total_seats" min="1" required>
            </div>

            <div class="form-group">
                <label class="required" for="skill_level">Skill Level</label>
                <select id="skill_level" name="skill_level" required>
                    <option value="">Select Level</option>
                    <option value="beginner">Beginner</option>
                    <option value="intermediate">Intermediate</option>
                    <option value="advanced">Advanced</option>
                </select>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">Add Class</button>
                <a href="teacher_dashboard.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <script>
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html> 