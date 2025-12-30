<?php
require_once 'config.php';
require_once 'auth_functions.php';
require_once 'db_functions.php';

require_user_type('user');

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$initials = get_initials($user_name);

// Get booking ID
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

if ($booking_id <= 0) {
    header("Location: my_bookings.php");
    exit();
}

// Get booking details
$booking = get_booking($booking_id);

if (!$booking || $booking['user_id'] != $user_id) {
    $_SESSION['error'] = 'Booking not found';
    header("Location: my_bookings.php");
    exit();
}

if ($booking['status'] !== 'completed') {
    $_SESSION['error'] = 'You can only review completed bookings';
    header("Location: my_bookings.php");
    exit();
}

// Check if review already exists
global $conn;
$check = $conn->prepare("SELECT id FROM reviews WHERE booking_id = ?");
$check->bind_param("i", $booking_id);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    $_SESSION['error'] = 'You have already reviewed this booking';
    header("Location: my_bookings.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = intval($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    
    if ($rating < 1 || $rating > 5) {
        $error = 'Please select a rating';
    } else {
        $result = create_review($booking_id, $user_id, $booking['worker_id'], $rating, $comment);
        
        if ($result['success']) {
            $_SESSION['success'] = 'Thank you for your review!';
            header("Location: my_bookings.php");
            exit();
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave a Review - ProTech</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f9fafb;
            color: #111827;
            min-height: 100vh;
        }
        .top-nav {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.5rem;
            font-weight: bold;
            color: #2563eb;
            text-decoration: none;
        }
        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logo-icon svg { width: 24px; height: 24px; fill: white; }
        .main-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 2rem;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #6b7280;
            text-decoration: none;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }
        .back-link:hover { color: #2563eb; }
        .back-link svg { width: 16px; height: 16px; stroke: currentColor; fill: none; stroke-width: 2; }
        .review-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            padding: 2rem;
        }
        .review-card h1 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .worker-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #f9fafb;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .worker-avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.25rem;
            flex-shrink: 0;
        }
        .worker-details h3 {
            font-size: 1.125rem;
            margin-bottom: 0.25rem;
        }
        .worker-details p {
            color: #6b7280;
            font-size: 0.875rem;
        }
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .rating-section {
            margin-bottom: 2rem;
            text-align: center;
        }
        .rating-section label {
            display: block;
            font-weight: 500;
            margin-bottom: 1rem;
            color: #374151;
        }
        .star-rating {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
        }
        .star-rating input {
            display: none;
        }
        .star-rating label {
            font-size: 2.5rem;
            color: #d1d5db;
            cursor: pointer;
            transition: color 0.2s;
        }
        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input:checked ~ label {
            color: #f59e0b;
        }
        .star-rating {
            flex-direction: row-reverse;
            justify-content: center;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #374151;
        }
        .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            resize: vertical;
            outline: none;
        }
        .form-group textarea:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        .btn {
            padding: 0.875rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            width: 100%;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        .rating-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 0.5rem;
            font-size: 0.75rem;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <nav class="top-nav">
        <a href="index.html" class="logo">
            <div class="logo-icon">
                <svg viewBox="0 0 512 512">
                    <path d="M323.5,140.1c-7.2-8.2-18.4-13-30.1-13h-1.5c-1.2,0.1-3.3,0.3-6.2,0.5c-4.5,0.3-10.6,0.8-17.9,1.3c-14.5,1-33.1,2.2-52.6,2.9c-38.9,1.4-83.1,0.2-113-10.1c-5.1-1.8-10.6-2.7-16.1-2.7c-11.9,0-23.3,4.8-31.6,13.4c-8.9,9.2-13.4,21.6-12.5,34.4l0,0.1l5.7,79.5c0.9,12.2,6.7,23.5,16.2,31.1l99.4,79.5c8.5,6.8,19,10.5,29.8,10.5c5.5,0,11-0.9,16.3-2.8l0.1,0c0.2-0.1,0.5-0.2,0.7-0.2l121.1-48.4c10.7-4.3,19.2-12.5,23.6-23.1c4.4-10.5,4.7-22.2,0.7-32.9L323.5,140.1z"/>
                    <path d="M471.1,119.3c-8.3-8.6-19.7-13.4-31.6-13.4c-5.5,0-11,0.9-16.1,2.7c-29.9,10.3-74.1,11.5-113,10.1c-19.5-0.7-38.1-1.9-52.6-2.9c-7.3-0.5-13.4-1-17.9-1.3c-2.9-0.2-5-0.4-6.2-0.5h-1.5c-11.8,0-22.9,4.7-30.1,13l-37.5,119.9c-4,10.8-3.7,22.4,0.7,32.9c4.4,10.5,12.9,18.7,23.6,23.1l121.1,48.4c0.2,0.1,0.5,0.2,0.7,0.2l0.1,0c5.3,1.9,10.8,2.8,16.3,2.8c10.8,0,21.4-3.7,29.8-10.5l99.4-79.5c9.5-7.6,15.3-18.9,16.2-31.1l5.7-79.5l0-0.1C484.5,140.9,480,128.5,471.1,119.3z"/>
                </svg>
            </div>
            ProTech
        </a>
    </nav>

    <div class="main-container">
        <a href="my_bookings.php" class="back-link">
            <svg viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></svg>
            Back to bookings
        </a>

        <div class="review-card">
            <h1>Leave a Review</h1>
            
            <div class="worker-info">
                <div class="worker-avatar"><?php echo get_initials($booking['worker_name']); ?></div>
                <div class="worker-details">
                    <h3><?php echo htmlspecialchars($booking['worker_name']); ?></h3>
                    <p><?php echo htmlspecialchars($booking['title']); ?></p>
                    <p><?php echo format_date($booking['scheduled_date']); ?></p>
                </div>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="rating-section">
                    <label>How was your experience?</label>
                    <div class="star-rating">
                        <input type="radio" name="rating" value="5" id="star5">
                        <label for="star5">★</label>
                        <input type="radio" name="rating" value="4" id="star4">
                        <label for="star4">★</label>
                        <input type="radio" name="rating" value="3" id="star3">
                        <label for="star3">★</label>
                        <input type="radio" name="rating" value="2" id="star2">
                        <label for="star2">★</label>
                        <input type="radio" name="rating" value="1" id="star1">
                        <label for="star1">★</label>
                    </div>
                    <div class="rating-labels">
                        <span>Poor</span>
                        <span>Excellent</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Your Review (optional)</label>
                    <textarea name="comment" rows="5" placeholder="Tell us about your experience..."><?php echo htmlspecialchars($_POST['comment'] ?? ''); ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Submit Review</button>
            </form>
        </div>
    </div>
</body>
</html>

