<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
    header('Location: login.php');
    exit();
}

if (isset($_GET['id'])) {
    try {
        // Start transaction
        $conn->beginTransaction();

        // Get class details to delete the image
        $stmt = $conn->prepare("
            SELECT image_path FROM classes 
            WHERE id = ? AND teacher_id = ?
        ");
        $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
        $class = $stmt->fetch();

        if ($class) {
            // Delete the class
            $stmt = $conn->prepare("DELETE FROM classes WHERE id = ? AND teacher_id = ?");
            $stmt->execute([$_GET['id'], $_SESSION['user_id']]);

            // Delete the image file if it exists
            if ($class['image_path'] && file_exists($class['image_path'])) {
                unlink($class['image_path']);
            }

            // Commit transaction
            $conn->commit();
            header('Location: teacher_dashboard.php?success=1');
        } else {
            throw new Exception("Class not found or you don't have permission to delete it");
        }
    } catch(Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        header('Location: teacher_dashboard.php?error=' . urlencode($e->getMessage()));
    }
} else {
    header('Location: teacher_dashboard.php');
}
exit(); 