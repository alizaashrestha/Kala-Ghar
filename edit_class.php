<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
    header('Location: login.php');
    exit();
}

$success = $error = '';

if (isset($_GET['id'])) {
    try {
        // Get class details
        $stmt = $conn->prepare("
            SELECT * FROM classes 
            WHERE id = ? AND teacher_id = ?
        ");
        $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
        $class = $stmt->fetch();

        if (!$class) {
            throw new Exception("Class not found or you don't have permission to edit it");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

            // Handle image upload
            $image_path = $class['image_path'];
            if (isset($_FILES['craft_image']) && $_FILES['craft_image']['error'] === UPLOAD_ERR_OK) {
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
                    // Delete old image if exists
                    if ($image_path && file_exists($image_path)) {
                        unlink($image_path);
                    }
                    $image_path = $target_path;
                } else {
                    throw new Exception("Failed to upload image");
                }
            }

            // Update class
            $stmt = $conn->prepare("
                UPDATE classes SET 
                    title = ?, description = ?, skill_type = ?, price = ?, 
                    duration = ?, schedule = ?, total_seats = ?, 
                    available_seats = ?, skill_level = ?, image_path = ?
                WHERE id = ? AND teacher_id = ?
            ");

            $stmt->execute([
                $_POST['title'],
                $_POST['description'],
                $_POST['skill_type'],
                $_POST['price'],
                $_POST['duration'],
                $_POST['schedule'],
                $_POST['total_seats'],
                $_POST['total_seats'], // Reset available seats to total seats
                $_POST['skill_level'],
                $image_path,
                $_GET['id'],
                $_SESSION['user_id']
            ]);

            $success = "Class updated successfully!";
            header("refresh:2;url=teacher_dashboard.php");
        }
    } catch(Exception $e) {
        $error = $e->getMessage();
    }
} else {
    header('Location: teacher_dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Class - Kala-Ghar</title>
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
        }
        .current-image {
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
                <a href="about.php">About</a>
                <a href="courses.php">Courses</a>
                <a href="contact.php">Contact</a>
                <a href="teacher_dashboard.php">Dashboard</a>
            </div>
        </nav>
    </header>

    <div class="form-container">
        <h2>Edit Class</h2>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label class="required" for="title">Class Title</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($class['title']); ?>" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($class['description']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="craft_image">Update Craft Image</label>
                <?php if ($class['image_path']): ?>
                    <div class="current-image">
                        <p>Current Image:</p>
                        <img src="<?php echo htmlspecialchars($class['image_path']); ?>" alt="Current craft image" class="preview-image">
                    </div>
                <?php endif; ?>
                <input type="file" id="craft_image" name="craft_image" accept="image/*" onchange="previewImage(this)">
                <img id="imagePreview" class="preview-image" src="#" alt="Preview" style="display: none;">
            </div>

            <div class="form-group">
                <label class="required" for="skill_type">Skill Type</label>
                <input type="text" id="skill_type" name="skill_type" value="<?php echo htmlspecialchars($class['skill_type']); ?>" required 
                       placeholder="e.g., Pottery, Painting, Knitting">
            </div>

            <div class="form-group">
                <label class="required" for="price">Price (₹)</label>
                <input type="number" id="price" name="price" min="0" step="0.01" value="<?php echo htmlspecialchars($class['price']); ?>" required>
            </div>

            <div class="form-group">
                <label for="duration">Duration</label>
                <input type="text" id="duration" name="duration" value="<?php echo htmlspecialchars($class['duration']); ?>" 
                       placeholder="e.g., 2 hours per session">
            </div>

            <div class="form-group">
                <label for="schedule">Schedule</label>
                <input type="text" id="schedule" name="schedule" value="<?php echo htmlspecialchars($class['schedule']); ?>" 
                       placeholder="e.g., Every Monday and Wednesday, 2-4 PM">
            </div>

            <div class="form-group">
                <label class="required" for="total_seats">Total Seats</label>
                <input type="number" id="total_seats" name="total_seats" min="1" value="<?php echo htmlspecialchars($class['total_seats']); ?>" required>
            </div>

            <div class="form-group">
                <label class="required" for="skill_level">Skill Level</label>
                <select id="skill_level" name="skill_level" required>
                    <option value="">Select Level</option>
                    <option value="beginner" <?php echo $class['skill_level'] === 'beginner' ? 'selected' : ''; ?>>Beginner</option>
                    <option value="intermediate" <?php echo $class['skill_level'] === 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                    <option value="advanced" <?php echo $class['skill_level'] === 'advanced' ? 'selected' : ''; ?>>Advanced</option>
                </select>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">Update Class</button>
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