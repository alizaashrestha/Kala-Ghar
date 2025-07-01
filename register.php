<?php
session_start();
require_once 'config/database.php';

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $user_type = $_POST['user_type'];
    $full_name = trim($_POST['full_name']);
    $location = trim($_POST['location']);
    $skills = trim($_POST['skills']);
    $phone_number = trim($_POST['phone_number']);

    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All required fields must be filled out";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        try {
            // Check if username exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = "Username already exists";
            } else {
                // Check if email exists
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = "Email already exists";
                } else {
                    // Insert new user
                    $stmt = $conn->prepare("
                        INSERT INTO users (username, email, password, user_type, full_name, location, skills, phone_number) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    $stmt->execute([
                        $username,
                        $email,
                        $hashed_password,
                        $user_type,
                        $full_name,
                        $location,
                        $skills,
                        $phone_number
                    ]);

                    $success = "Registration successful! You can now login.";
                    
                    // Redirect to login page after 2 seconds
                    header("refresh:2;url=login.php");
                }
            }
        } catch(PDOException $e) {
            $error = "Registration failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Kala-Ghar</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .form-container {
            max-width: 600px;
        }
        .user-type-selector {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .user-type-selector label {
            flex: 1;
            padding: 1rem;
            text-align: center;
            border: 2px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .user-type-selector input[type="radio"] {
            display: none;
        }
        .user-type-selector input[type="radio"]:checked + label {
            border-color: #3498db;
            background: #3498db;
            color: white;
        }
        .skills-field {
            display: none;
        }
        .skills-field.show {
            display: block;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">Kala-Ghar</div>
            <div class="nav-links">
                <a href="index.php">Home</a>
            </div>
        </nav>
    </header>

    <div class="form-container">
        <h2>Create Account</h2>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="" id="registerForm">
            <div class="user-type-selector">
                <input type="radio" id="student" name="user_type" value="student" checked>
                <label for="student">Student</label>
                
                <input type="radio" id="teacher" name="user_type" value="teacher">
                <label for="teacher">Teacher</label>
            </div>

            <div class="form-group">
                <label for="username">Username *</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password *</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name">
            </div>

            <div class="form-group">
                <label for="location">Location</label>
                <input type="text" id="location" name="location">
            </div>

            <div class="form-group skills-field">
                <label for="skills">Skills (For teachers: list your handicraft skills)</label>
                <textarea id="skills" name="skills" rows="3"></textarea>
            </div>

            <div class="form-group">
                <label for="phone_number">Phone Number</label>
                <input type="tel" pattern="^(97|98)\d{8}$" required>
            </div>

            <button type="submit" class="btn btn-primary">Register</button>
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const userTypeInputs = document.querySelectorAll('input[name="user_type"]');
            const skillsField = document.querySelector('.skills-field');

            function toggleSkillsField() {
                if (document.querySelector('input[name="user_type"]:checked').value === 'teacher') {
                    skillsField.classList.add('show');
                } else {
                    skillsField.classList.remove('show');
                }
            }

            userTypeInputs.forEach(input => {
                input.addEventListener('change', toggleSkillsField);
            });

            // Initial check
            toggleSkillsField();
        });
    </script>
</body>
</html> 