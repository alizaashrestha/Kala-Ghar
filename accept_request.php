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

        // Get the booking request and class details
        $stmt = $conn->prepare("
            SELECT br.*, c.available_seats 
            FROM booking_requests br 
            JOIN classes c ON br.class_id = c.id 
            WHERE br.id = ? AND c.teacher_id = ? AND br.status = 'pending'
        ");
        $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
        $request = $stmt->fetch();

        if ($request && $request['available_seats'] > 0) {
            // Update booking request status
            $stmt = $conn->prepare("UPDATE booking_requests SET status = 'accepted' WHERE id = ?");
            $stmt->execute([$_GET['id']]);

            // Decrease available seats
            $stmt = $conn->prepare("UPDATE classes SET available_seats = available_seats - 1 WHERE id = ?");
            $stmt->execute([$request['class_id']]);

            // Commit transaction
            $conn->commit();

            header('Location: teacher_dashboard.php?success=1');
        } else {
            throw new Exception("No seats available or invalid request");
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