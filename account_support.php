<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate input
        if (empty($_POST['request_type']) || empty($_POST['message'])) {
            throw new Exception("Please fill in all required fields");
        }

        // Insert support request
        $stmt = $conn->prepare("
            INSERT INTO support_requests (
                user_id, 
                request_type, 
                message, 
                status
            ) VALUES (?, ?, ?, 'pending')
        ");

        $stmt->execute([
            $_SESSION['user_id'],
            $_POST['request_type'],
            $_POST['message']
        ]);

        $success = "Your request has been submitted successfully. Admin will review it shortly.";
    } catch(Exception $e) {
        $error = $e->getMessage();
    }
}

// Get user's previous requests
$stmt = $conn->prepare("
    SELECT * FROM support_requests 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$previous_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Support - Kala-Ghar</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .support-container {
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
            font-weight: bold;
        }
        .form-control {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        textarea.form-control {
            min-height: 150px;
        }
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
        }
        .request-history {
            margin-top: 2rem;
        }
        .request-card {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .request-type {
            font-weight: bold;
            color: #333;
        }
        .request-status {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            margin-left: 0.5rem;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-resolved {
            background: #d4edda;
            color: #155724;
        }
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }
        .request-message {
            margin-top: 0.5rem;
            color: #666;
        }
        .request-date {
            font-size: 0.9rem;
            color: #888;
            margin-top: 0.5rem;
        }
        .admin-response {
            margin-top: 0.5rem;
            padding: 0.5rem;
            background: #e9ecef;
            border-radius: 4px;
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
            </div>
        </nav>
    </header>

    <div class="support-container">
        <h2>Account Support</h2>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="request_type">Request Type *</label>
                <select name="request_type" id="request_type" class="form-control" required>
                    <option value="">Select Request Type</option>
                    <option value="delete_account">Delete Account</option>
                    <option value="update_info">Update Account Information</option>
                    <option value="reset_password">Reset Password</option>
                    <option value="other">Other Account Issue</option>
                </select>
            </div>

            <div class="form-group">
                <label for="message">Message *</label>
                <textarea name="message" id="message" class="form-control" required
                    placeholder="Please describe your request in detail..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Submit Request</button>
        </form>

        <?php if ($previous_requests): ?>
            <div class="request-history">
                <h3>Previous Requests</h3>
                <?php foreach ($previous_requests as $request): ?>
                    <div class="request-card">
                        <div>
                            <span class="request-type">
                                <?php 
                                    $types = [
                                        'delete_account' => 'Delete Account',
                                        'update_info' => 'Update Account Information',
                                        'reset_password' => 'Reset Password',
                                        'other' => 'Other Account Issue'
                                    ];
                                    echo $types[$request['request_type']] ?? $request['request_type'];
                                ?>
                            </span>
                            <span class="request-status status-<?php echo $request['status']; ?>">
                                <?php echo ucfirst($request['status']); ?>
                            </span>
                        </div>
                        <div class="request-message">
                            <?php echo nl2br(htmlspecialchars($request['message'])); ?>
                        </div>
                        <?php if ($request['admin_response']): ?>
                            <div class="admin-response">
                                <strong>Admin Response:</strong><br>
                                <?php echo nl2br(htmlspecialchars($request['admin_response'])); ?>
                            </div>
                        <?php endif; ?>
                        <div class="request-date">
                            Submitted on: <?php echo date('F j, Y g:i A', strtotime($request['created_at'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 