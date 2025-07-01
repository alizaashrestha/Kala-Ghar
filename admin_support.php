<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$success = $error = '';

// Handle admin response submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (empty($_POST['request_id']) || empty($_POST['admin_response']) || empty($_POST['status'])) {
            throw new Exception("Please fill in all required fields");
        }

        // Update support request
        $stmt = $conn->prepare("
            UPDATE support_requests 
            SET admin_response = ?, status = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");

        $stmt->execute([
            $_POST['admin_response'],
            $_POST['status'],
            $_POST['request_id']
        ]);

        // If request was for account deletion and it's approved
        if ($_POST['status'] === 'resolved' && $_POST['request_type'] === 'delete_account') {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$_POST['user_id']]);
        }

        $success = "Response submitted successfully.";
    } catch(Exception $e) {
        $error = $e->getMessage();
    }
}

// Get all support requests with user information
$stmt = $conn->prepare("
    SELECT sr.*, u.full_name, u.email, u.user_type
    FROM support_requests sr
    JOIN users u ON sr.user_id = u.id
    ORDER BY 
        CASE sr.status 
            WHEN 'pending' THEN 1
            WHEN 'resolved' THEN 2
            WHEN 'rejected' THEN 3
        END,
        sr.created_at DESC
");
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Requests - Admin Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
        }
        .request-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }
        .user-info {
            margin-bottom: 1rem;
        }
        .request-message {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .response-form {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 4px;
        }
        .form-group {
            margin-bottom: 1rem;
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
            min-height: 100px;
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
        .status-resolved {
            background: #d4edda;
            color: #155724;
        }
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
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
        .admin-response {
            background: #e9ecef;
            padding: 1rem;
            border-radius: 4px;
            margin-top: 1rem;
        }
        .btn-group {
            display: flex;
            gap: 0.5rem;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">Kala-Ghar</div>
            <div class="nav-links">
                <a href="admin_dashboard.php">Dashboard</a>
                <a href="admin_users.php">Users</a>
                <a href="admin_classes.php">Classes</a>
                <a href="admin_support.php">Support</a>
                <a href="logout.php">Logout</a>
            </div>
        </nav>
    </header>

    <div class="admin-container">
        <h2>Support Requests</h2>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php foreach ($requests as $request): ?>
            <div class="request-card">
                <div class="request-header">
                    <div>
                        <span class="status-badge status-<?php echo $request['status']; ?>">
                            <?php echo ucfirst($request['status']); ?>
                        </span>
                        <span style="margin-left: 1rem;">
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
                    </div>
                    <small>Submitted: <?php echo date('F j, Y g:i A', strtotime($request['created_at'])); ?></small>
                </div>

                <div class="user-info">
                    <p><strong>User:</strong> <?php echo htmlspecialchars($request['full_name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($request['email']); ?></p>
                    <p><strong>Account Type:</strong> <?php echo ucfirst(htmlspecialchars($request['user_type'])); ?></p>
                </div>

                <div class="request-message">
                    <strong>Request Message:</strong><br>
                    <?php echo nl2br(htmlspecialchars($request['message'])); ?>
                </div>

                <?php if ($request['admin_response']): ?>
                    <div class="admin-response">
                        <strong>Admin Response:</strong><br>
                        <?php echo nl2br(htmlspecialchars($request['admin_response'])); ?>
                    </div>
                <?php endif; ?>

                <?php if ($request['status'] === 'pending'): ?>
                    <form method="POST" action="" class="response-form">
                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                        <input type="hidden" name="request_type" value="<?php echo $request['request_type']; ?>">
                        <input type="hidden" name="user_id" value="<?php echo $request['user_id']; ?>">
                        
                        <div class="form-group">
                            <label for="admin_response_<?php echo $request['id']; ?>">Your Response</label>
                            <textarea name="admin_response" id="admin_response_<?php echo $request['id']; ?>" 
                                    class="form-control" required></textarea>
                        </div>

                        <div class="form-group">
                            <label for="status_<?php echo $request['id']; ?>">Status</label>
                            <select name="status" id="status_<?php echo $request['id']; ?>" class="form-control" required>
                                <option value="">Select Status</option>
                                <option value="resolved">Resolve</option>
                                <option value="rejected">Reject</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">Submit Response</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html> 