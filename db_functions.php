<?php
require_once 'config.php';

// ==================== WORKER FUNCTIONS ====================

// Get all available workers with optional filters
function get_workers($filters = []) {
    global $conn;
    
    $sql = "SELECT w.*, u.name, u.email, u.phone, u.avatar 
            FROM workers w 
            JOIN users u ON w.user_id = u.id 
            WHERE 1=1";
    $params = [];
    $types = "";
    
    if (!empty($filters['skill'])) {
        $sql .= " AND w.skills LIKE ?";
        $params[] = "%" . $filters['skill'] . "%";
        $types .= "s";
    }
    
    if (!empty($filters['service_area'])) {
        $sql .= " AND w.service_area LIKE ?";
        $params[] = "%" . $filters['service_area'] . "%";
        $types .= "s";
    }
    
    if (!empty($filters['min_rating'])) {
        $sql .= " AND w.rating >= ?";
        $params[] = $filters['min_rating'];
        $types .= "d";
    }
    
    if (isset($filters['is_available'])) {
        $sql .= " AND w.is_available = ?";
        $params[] = $filters['is_available'] ? 1 : 0;
        $types .= "i";
    }
    
    $sql .= " ORDER BY w.rating DESC, w.total_jobs DESC";
    
    if (!empty($filters['limit'])) {
        $sql .= " LIMIT ?";
        $params[] = $filters['limit'];
        $types .= "i";
    }
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get single worker by ID
function get_worker($worker_id) {
    global $conn;
    $sql = "SELECT w.*, u.name, u.email, u.phone, u.avatar 
            FROM workers w 
            JOIN users u ON w.user_id = u.id 
            WHERE w.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $worker_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Get worker by user_id
function get_worker_by_user_id($user_id) {
    global $conn;
    $sql = "SELECT w.*, u.name, u.email, u.phone, u.avatar 
            FROM workers w 
            JOIN users u ON w.user_id = u.id 
            WHERE w.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// ==================== BOOKING FUNCTIONS ====================

// Create a new booking
function create_booking($user_id, $worker_id, $data) {
    global $conn;
    
    $sql = "INSERT INTO bookings (user_id, worker_id, service_category_id, title, description, 
            scheduled_date, scheduled_time, estimated_duration, address, price) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiissssisd", 
        $user_id, 
        $worker_id, 
        $data['service_category_id'],
        $data['title'],
        $data['description'],
        $data['scheduled_date'],
        $data['scheduled_time'],
        $data['estimated_duration'],
        $data['address'],
        $data['price']
    );
    
    if ($stmt->execute()) {
        $booking_id = $stmt->insert_id;
        
        // Create notification for worker
        $worker = get_worker($worker_id);
        create_notification($worker['user_id'], 'New Job Request', 
            "You have a new booking request: " . $data['title'], 
            'booking', "booking.php?id=$booking_id");
        
        return ['success' => true, 'booking_id' => $booking_id];
    }
    return ['success' => false, 'message' => 'Failed to create booking'];
}

// Get bookings for a user (client)
function get_user_bookings($user_id, $status = null, $limit = null) {
    global $conn;
    
    $sql = "SELECT b.*, w.id as worker_id, u.name as worker_name, u.avatar as worker_avatar,
            sc.name as service_name
            FROM bookings b 
            JOIN workers w ON b.worker_id = w.id 
            JOIN users u ON w.user_id = u.id
            LEFT JOIN service_categories sc ON b.service_category_id = sc.id
            WHERE b.user_id = ?";
    $params = [$user_id];
    $types = "i";
    
    if ($status) {
        $sql .= " AND b.status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    $sql .= " ORDER BY b.scheduled_date DESC, b.scheduled_time DESC";
    
    if ($limit) {
        $sql .= " LIMIT ?";
        $params[] = $limit;
        $types .= "i";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get bookings for a worker
function get_worker_bookings($worker_id, $status = null, $limit = null) {
    global $conn;
    
    $sql = "SELECT b.*, u.name as client_name, u.phone as client_phone, u.avatar as client_avatar,
            sc.name as service_name
            FROM bookings b 
            JOIN users u ON b.user_id = u.id
            LEFT JOIN service_categories sc ON b.service_category_id = sc.id
            WHERE b.worker_id = ?";
    $params = [$worker_id];
    $types = "i";
    
    if ($status) {
        if (is_array($status)) {
            $placeholders = implode(',', array_fill(0, count($status), '?'));
            $sql .= " AND b.status IN ($placeholders)";
            foreach ($status as $s) {
                $params[] = $s;
                $types .= "s";
            }
        } else {
            $sql .= " AND b.status = ?";
            $params[] = $status;
            $types .= "s";
        }
    }
    
    $sql .= " ORDER BY b.scheduled_date ASC, b.scheduled_time ASC";
    
    if ($limit) {
        $sql .= " LIMIT ?";
        $params[] = $limit;
        $types .= "i";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get today's schedule for a worker
function get_worker_today_schedule($worker_id) {
    global $conn;
    
    $today = date('Y-m-d');
    $sql = "SELECT b.*, u.name as client_name, u.phone as client_phone
            FROM bookings b 
            JOIN users u ON b.user_id = u.id
            WHERE b.worker_id = ? AND b.scheduled_date = ? 
            AND b.status IN ('confirmed', 'in_progress')
            ORDER BY b.scheduled_time ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $worker_id, $today);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Update booking status
function update_booking_status($booking_id, $status, $user_id = null) {
    global $conn;
    
    // Verify the user has permission to update this booking
    $sql = "UPDATE bookings SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $booking_id);
    
    if ($stmt->execute()) {
        // Get booking details for notification
        $booking = get_booking($booking_id);
        
        // Create notifications
        if ($status === 'confirmed') {
            create_notification($booking['user_id'], 'Booking Confirmed', 
                "Your booking '{$booking['title']}' has been confirmed!", 
                'booking', "booking.php?id=$booking_id");
        } elseif ($status === 'completed') {
            create_notification($booking['user_id'], 'Service Completed', 
                "Your booking '{$booking['title']}' has been completed. Please leave a review!", 
                'booking', "review.php?booking_id=$booking_id");
        } elseif ($status === 'cancelled') {
            // Notify both parties
            $worker = get_worker($booking['worker_id']);
            create_notification($booking['user_id'], 'Booking Cancelled', 
                "Your booking '{$booking['title']}' has been cancelled.", 'booking');
            create_notification($worker['user_id'], 'Booking Cancelled', 
                "A booking '{$booking['title']}' has been cancelled.", 'booking');
        }
        
        return ['success' => true];
    }
    return ['success' => false, 'message' => 'Failed to update booking'];
}

// Get single booking
function get_booking($booking_id) {
    global $conn;
    
    $sql = "SELECT b.*, 
            u.name as client_name, u.phone as client_phone, u.email as client_email,
            wu.name as worker_name, wu.phone as worker_phone,
            w.skills, w.service_area,
            sc.name as service_name
            FROM bookings b 
            JOIN users u ON b.user_id = u.id
            JOIN workers w ON b.worker_id = w.id
            JOIN users wu ON w.user_id = wu.id
            LEFT JOIN service_categories sc ON b.service_category_id = sc.id
            WHERE b.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// ==================== STATISTICS FUNCTIONS ====================

// Get user statistics
function get_user_stats($user_id) {
    global $conn;
    
    $stats = [];
    
    // Active bookings
    $sql = "SELECT COUNT(*) as count FROM bookings WHERE user_id = ? AND status IN ('pending', 'confirmed', 'in_progress')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stats['active_bookings'] = $stmt->get_result()->fetch_assoc()['count'];
    
    // Completed services
    $sql = "SELECT COUNT(*) as count FROM bookings WHERE user_id = ? AND status = 'completed'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stats['completed_services'] = $stmt->get_result()->fetch_assoc()['count'];
    
    // Total spent
    $sql = "SELECT COALESCE(SUM(price), 0) as total FROM bookings WHERE user_id = ? AND status = 'completed' AND payment_status = 'paid'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stats['total_spent'] = $stmt->get_result()->fetch_assoc()['total'];
    
    // Reviews given
    $sql = "SELECT COUNT(*) as count FROM reviews WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stats['reviews_given'] = $stmt->get_result()->fetch_assoc()['count'];
    
    return $stats;
}

// Get worker statistics
function get_worker_stats($worker_id) {
    global $conn;
    
    $stats = [];
    
    // This month's earnings
    $first_day = date('Y-m-01');
    $sql = "SELECT COALESCE(SUM(price), 0) as total FROM bookings 
            WHERE worker_id = ? AND status = 'completed' AND scheduled_date >= ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $worker_id, $first_day);
    $stmt->execute();
    $stats['monthly_earnings'] = $stmt->get_result()->fetch_assoc()['total'];
    
    // Pending requests
    $sql = "SELECT COUNT(*) as count FROM bookings WHERE worker_id = ? AND status = 'pending'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $worker_id);
    $stmt->execute();
    $stats['pending_requests'] = $stmt->get_result()->fetch_assoc()['count'];
    
    // Scheduled jobs
    $sql = "SELECT COUNT(*) as count FROM bookings WHERE worker_id = ? AND status IN ('confirmed', 'in_progress')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $worker_id);
    $stmt->execute();
    $stats['scheduled_jobs'] = $stmt->get_result()->fetch_assoc()['count'];
    
    // Get worker rating info
    $worker = get_worker($worker_id);
    $stats['rating'] = $worker['rating'];
    $stats['total_reviews'] = $worker['total_reviews'];
    $stats['total_jobs'] = $worker['total_jobs'];
    
    return $stats;
}

// ==================== REVIEW FUNCTIONS ====================

// Create a review
function create_review($booking_id, $user_id, $worker_id, $rating, $comment) {
    global $conn;
    
    // Check if review already exists
    $check = "SELECT id FROM reviews WHERE booking_id = ?";
    $stmt = $conn->prepare($check);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => 'Review already exists for this booking'];
    }
    
    $sql = "INSERT INTO reviews (booking_id, user_id, worker_id, rating, comment) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiis", $booking_id, $user_id, $worker_id, $rating, $comment);
    
    if ($stmt->execute()) {
        // Notify worker about the review
        $worker = get_worker($worker_id);
        create_notification($worker['user_id'], 'New Review', 
            "You received a new $rating-star review!", 'review');
        
        return ['success' => true];
    }
    return ['success' => false, 'message' => 'Failed to create review'];
}

// Get reviews for a worker
function get_worker_reviews($worker_id, $limit = 10) {
    global $conn;
    
    $sql = "SELECT r.*, u.name as reviewer_name, u.avatar as reviewer_avatar, b.title as booking_title
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            JOIN bookings b ON r.booking_id = b.id
            WHERE r.worker_id = ?
            ORDER BY r.created_at DESC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $worker_id, $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get pending reviews for a user (completed bookings without reviews)
function get_pending_reviews($user_id) {
    global $conn;
    
    $sql = "SELECT b.*, wu.name as worker_name
            FROM bookings b
            JOIN workers w ON b.worker_id = w.id
            JOIN users wu ON w.user_id = wu.id
            LEFT JOIN reviews r ON b.id = r.booking_id
            WHERE b.user_id = ? AND b.status = 'completed' AND r.id IS NULL
            ORDER BY b.scheduled_date DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// ==================== NOTIFICATION FUNCTIONS ====================

// Create notification
function create_notification($user_id, $title, $message, $type = 'system', $link = null) {
    global $conn;
    
    $sql = "INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $user_id, $title, $message, $type, $link);
    return $stmt->execute();
}

// Get user notifications
function get_notifications($user_id, $unread_only = false, $limit = 20) {
    global $conn;
    
    $sql = "SELECT * FROM notifications WHERE user_id = ?";
    if ($unread_only) {
        $sql .= " AND is_read = FALSE";
    }
    $sql .= " ORDER BY created_at DESC LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Count unread notifications
function count_unread_notifications($user_id) {
    global $conn;
    
    $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['count'];
}

// Mark notification as read
function mark_notification_read($notification_id) {
    global $conn;
    
    $sql = "UPDATE notifications SET is_read = TRUE WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $notification_id);
    return $stmt->execute();
}

// ==================== SERVICE CATEGORIES ====================

function get_service_categories() {
    global $conn;
    
    $sql = "SELECT * FROM service_categories ORDER BY name";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// ==================== FAVORITES ====================

function add_favorite($user_id, $worker_id) {
    global $conn;
    
    $sql = "INSERT IGNORE INTO favorites (user_id, worker_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $worker_id);
    return $stmt->execute();
}

function remove_favorite($user_id, $worker_id) {
    global $conn;
    
    $sql = "DELETE FROM favorites WHERE user_id = ? AND worker_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $worker_id);
    return $stmt->execute();
}

function get_user_favorites($user_id) {
    global $conn;
    
    $sql = "SELECT w.*, u.name, u.avatar 
            FROM favorites f
            JOIN workers w ON f.worker_id = w.id
            JOIN users u ON w.user_id = u.id
            WHERE f.user_id = ?
            ORDER BY f.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function is_favorite($user_id, $worker_id) {
    global $conn;
    
    $sql = "SELECT id FROM favorites WHERE user_id = ? AND worker_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $worker_id);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

// ==================== HELPER FUNCTIONS ====================

function get_initials($name) {
    $words = explode(' ', trim($name));
    $initials = '';
    foreach ($words as $word) {
        if (!empty($word)) {
            $initials .= strtoupper($word[0]);
        }
    }
    return substr($initials, 0, 2);
}

function format_date($date) {
    return date('M d, Y', strtotime($date));
}

function format_time($time) {
    return date('g:i A', strtotime($time));
}

function format_price($price) {
    return '$' . number_format($price, 2);
}

function time_ago($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}
?>

