<?php
require_once 'config.php';
require_once 'auth_functions.php';
require_once 'db_functions.php';

require_login();

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . ($user_type === 'worker' ? 'worker.php' : 'user.php'));
    exit();
}

$booking_id = intval($_POST['booking_id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($booking_id <= 0 || empty($action)) {
    $_SESSION['error'] = 'Invalid request';
    header("Location: " . ($user_type === 'worker' ? 'worker.php' : 'user.php'));
    exit();
}

// Get booking details to verify ownership
$booking = get_booking($booking_id);

if (!$booking) {
    $_SESSION['error'] = 'Booking not found';
    header("Location: " . ($user_type === 'worker' ? 'worker.php' : 'user.php'));
    exit();
}

// Handle different actions based on user type
if ($user_type === 'worker') {
    // Get worker ID for this user
    $worker = get_worker_by_user_id($user_id);
    
    if (!$worker || $booking['worker_id'] != $worker['id']) {
        $_SESSION['error'] = 'Unauthorized action';
        header("Location: worker.php");
        exit();
    }
    
    switch ($action) {
        case 'accept':
            $result = update_booking_status($booking_id, 'confirmed');
            if ($result['success']) {
                $_SESSION['success'] = 'Booking accepted successfully!';
            } else {
                $_SESSION['error'] = $result['message'];
            }
            break;
            
        case 'decline':
            $result = update_booking_status($booking_id, 'cancelled');
            if ($result['success']) {
                $_SESSION['success'] = 'Booking declined.';
            } else {
                $_SESSION['error'] = $result['message'];
            }
            break;
            
        case 'start':
            $result = update_booking_status($booking_id, 'in_progress');
            if ($result['success']) {
                $_SESSION['success'] = 'Job started!';
            } else {
                $_SESSION['error'] = $result['message'];
            }
            break;
            
        case 'complete':
            $result = update_booking_status($booking_id, 'completed');
            if ($result['success']) {
                $_SESSION['success'] = 'Job marked as completed!';
            } else {
                $_SESSION['error'] = $result['message'];
            }
            break;
            
        default:
            $_SESSION['error'] = 'Invalid action';
    }
    
    header("Location: worker.php");
    exit();
    
} else {
    // User actions
    if ($booking['user_id'] != $user_id) {
        $_SESSION['error'] = 'Unauthorized action';
        header("Location: user.php");
        exit();
    }
    
    switch ($action) {
        case 'cancel':
            // Only allow cancellation if booking is pending or confirmed
            if (in_array($booking['status'], ['pending', 'confirmed'])) {
                $result = update_booking_status($booking_id, 'cancelled');
                if ($result['success']) {
                    $_SESSION['success'] = 'Booking cancelled successfully.';
                } else {
                    $_SESSION['error'] = $result['message'];
                }
            } else {
                $_SESSION['error'] = 'Cannot cancel this booking';
            }
            break;
            
        default:
            $_SESSION['error'] = 'Invalid action';
    }
    
    header("Location: my_bookings.php");
    exit();
}
?>

