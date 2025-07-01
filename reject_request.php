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
        // Get the booking request and verify it belongs to the teacher
        $stmt = $conn->prepare("
            SELECT br.* 
            FROM booking_requests br 
            JOIN classes c ON br.class_id = c.id 
            WHERE br.id = ? AND c.teacher_id = ? AND br.status = 'pending'
        ");
        $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
        $request = $stmt->fetch();

        if ($request) {
            // Update booking request status
            $stmt = $conn->prepare("UPDATE booking_requests SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$_GET['id']]);

            header('Location: teacher_dashboard.php?success=2');
        } else {
            throw new Exception("Invalid request");
        }
    } catch(Exception $e) {
        header('Location: teacher_dashboard.php?error=' . urlencode($e->getMessage()));
    }
} else {
    header('Location: teacher_dashboard.php');
}
exit(); 