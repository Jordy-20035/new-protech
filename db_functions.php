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
    
    if (!empty($filters['skill'])) {
        $sql .= " AND w.skills ILIKE :skill";
        $params['skill'] = "%" . $filters['skill'] . "%";
    }
    
    if (!empty($filters['service_area'])) {
        $sql .= " AND w.service_area ILIKE :service_area";
        $params['service_area'] = "%" . $filters['service_area'] . "%";
    }
    
    if (!empty($filters['min_rating'])) {
        $sql .= " AND w.rating >= :min_rating";
        $params['min_rating'] = $filters['min_rating'];
    }
    
    if (isset($filters['is_available'])) {
        $sql .= " AND w.is_available = :is_available";
        $params['is_available'] = $filters['is_available'] ? 't' : 'f';
    }
    
    $sql .= " ORDER BY w.rating DESC, w.total_jobs DESC";
    
    if (!empty($filters['limit'])) {
        $sql .= " LIMIT :limit";
        $params['limit'] = $filters['limit'];
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Get single worker by ID
function get_worker($worker_id) {
    global $conn;
    $sql = "SELECT w.*, u.name, u.email, u.phone, u.avatar 
            FROM workers w 
            JOIN users u ON w.user_id = u.id 
            WHERE w.id = :worker_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['worker_id' => $worker_id]);
    return $stmt->fetch();
}

// Get worker by user_id
function get_worker_by_user_id($user_id) {
    global $conn;
    $sql = "SELECT w.*, u.name, u.email, u.phone, u.avatar 
            FROM workers w 
            JOIN users u ON w.user_id = u.id 
            WHERE w.user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);
    return $stmt->fetch();
}

// ==================== BOOKING FUNCTIONS ====================

// Create a new booking
function create_booking($user_id, $worker_id, $data) {
    global $conn;
    
    try {
        $sql = "INSERT INTO bookings (user_id, worker_id, service_category_id, title, description, 
                scheduled_date, scheduled_time, estimated_duration, address, price) 
                VALUES (:user_id, :worker_id, :service_category_id, :title, :description, 
                :scheduled_date, :scheduled_time, :estimated_duration, :address, :price)";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'user_id' => $user_id,
            'worker_id' => $worker_id,
            'service_category_id' => $data['service_category_id'] ?: null,
            'title' => $data['title'],
            'description' => $data['description'],
            'scheduled_date' => $data['scheduled_date'],
            'scheduled_time' => $data['scheduled_time'],
            'estimated_duration' => $data['estimated_duration'],
            'address' => $data['address'],
            'price' => $data['price']
        ]);
        
        $booking_id = $conn->lastInsertId();
        
        // Create notification for worker
        $worker = get_worker($worker_id);
        create_notification($worker['user_id'], 'New Job Request', 
            "You have a new booking request: " . $data['title'], 
            'booking', "booking.php?id=$booking_id");
        
        return ['success' => true, 'booking_id' => $booking_id];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Failed to create booking: ' . $e->getMessage()];
    }
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
            WHERE b.user_id = :user_id";
    $params = ['user_id' => $user_id];
    
    if ($status) {
        $sql .= " AND b.status = :status";
        $params['status'] = $status;
    }
    
    $sql .= " ORDER BY b.scheduled_date DESC, b.scheduled_time DESC";
    
    if ($limit) {
        $sql .= " LIMIT :limit";
        $params['limit'] = $limit;
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Get bookings for a worker
function get_worker_bookings($worker_id, $status = null, $limit = null) {
    global $conn;
    
    $sql = "SELECT b.*, u.name as client_name, u.phone as client_phone, u.avatar as client_avatar,
            sc.name as service_name
            FROM bookings b 
            JOIN users u ON b.user_id = u.id
            LEFT JOIN service_categories sc ON b.service_category_id = sc.id
            WHERE b.worker_id = :worker_id";
    $params = ['worker_id' => $worker_id];
    
    if ($status) {
        if (is_array($status)) {
            $placeholders = [];
            foreach ($status as $i => $s) {
                $key = "status_$i";
                $placeholders[] = ":$key";
                $params[$key] = $s;
            }
            $sql .= " AND b.status IN (" . implode(',', $placeholders) . ")";
        } else {
            $sql .= " AND b.status = :status";
            $params['status'] = $status;
        }
    }
    
    $sql .= " ORDER BY b.scheduled_date ASC, b.scheduled_time ASC";
    
    if ($limit) {
        $sql .= " LIMIT :limit";
        $params['limit'] = $limit;
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Get today's schedule for a worker
function get_worker_today_schedule($worker_id) {
    global $conn;
    
    $today = date('Y-m-d');
    $sql = "SELECT b.*, u.name as client_name, u.phone as client_phone
            FROM bookings b 
            JOIN users u ON b.user_id = u.id
            WHERE b.worker_id = :worker_id AND b.scheduled_date = :today 
            AND b.status IN ('confirmed', 'in_progress')
            ORDER BY b.scheduled_time ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute(['worker_id' => $worker_id, 'today' => $today]);
    return $stmt->fetchAll();
}

// Update booking status
function update_booking_status($booking_id, $status, $user_id = null) {
    global $conn;
    
    try {
        $sql = "UPDATE bookings SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :booking_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['status' => $status, 'booking_id' => $booking_id]);
        
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
            $worker = get_worker($booking['worker_id']);
            create_notification($booking['user_id'], 'Booking Cancelled', 
                "Your booking '{$booking['title']}' has been cancelled.", 'booking');
            create_notification($worker['user_id'], 'Booking Cancelled', 
                "A booking '{$booking['title']}' has been cancelled.", 'booking');
        }
        
        return ['success' => true];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Failed to update booking: ' . $e->getMessage()];
    }
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
            WHERE b.id = :booking_id";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute(['booking_id' => $booking_id]);
    return $stmt->fetch();
}

// ==================== STATISTICS FUNCTIONS ====================

// Get user statistics
function get_user_stats($user_id) {
    global $conn;
    
    $stats = [];
    
    // Active bookings
    $sql = "SELECT COUNT(*) as count FROM bookings WHERE user_id = :user_id AND status IN ('pending', 'confirmed', 'in_progress')";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);
    $stats['active_bookings'] = $stmt->fetch()['count'];
    
    // Completed services
    $sql = "SELECT COUNT(*) as count FROM bookings WHERE user_id = :user_id AND status = 'completed'";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);
    $stats['completed_services'] = $stmt->fetch()['count'];
    
    // Total spent
    $sql = "SELECT COALESCE(SUM(price), 0) as total FROM bookings WHERE user_id = :user_id AND status = 'completed' AND payment_status = 'paid'";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);
    $stats['total_spent'] = $stmt->fetch()['total'];
    
    // Reviews given
    $sql = "SELECT COUNT(*) as count FROM reviews WHERE user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);
    $stats['reviews_given'] = $stmt->fetch()['count'];
    
    return $stats;
}

// Get worker statistics
function get_worker_stats($worker_id) {
    global $conn;
    
    $stats = [];
    
    // This month's earnings
    $first_day = date('Y-m-01');
    $sql = "SELECT COALESCE(SUM(price), 0) as total FROM bookings 
            WHERE worker_id = :worker_id AND status = 'completed' AND scheduled_date >= :first_day";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['worker_id' => $worker_id, 'first_day' => $first_day]);
    $stats['monthly_earnings'] = $stmt->fetch()['total'];
    
    // Pending requests
    $sql = "SELECT COUNT(*) as count FROM bookings WHERE worker_id = :worker_id AND status = 'pending'";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['worker_id' => $worker_id]);
    $stats['pending_requests'] = $stmt->fetch()['count'];
    
    // Scheduled jobs
    $sql = "SELECT COUNT(*) as count FROM bookings WHERE worker_id = :worker_id AND status IN ('confirmed', 'in_progress')";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['worker_id' => $worker_id]);
    $stats['scheduled_jobs'] = $stmt->fetch()['count'];
    
    // Get worker rating info
    $worker = get_worker($worker_id);
    $stats['rating'] = $worker['rating'] ?? 0;
    $stats['total_reviews'] = $worker['total_reviews'] ?? 0;
    $stats['total_jobs'] = $worker['total_jobs'] ?? 0;
    
    return $stats;
}

// ==================== REVIEW FUNCTIONS ====================

// Create a review
function create_review($booking_id, $user_id, $worker_id, $rating, $comment) {
    global $conn;
    
    try {
        // Check if review already exists
        $check = $conn->prepare("SELECT id FROM reviews WHERE booking_id = :booking_id");
        $check->execute(['booking_id' => $booking_id]);
        if ($check->rowCount() > 0) {
            return ['success' => false, 'message' => 'Review already exists for this booking'];
        }
        
        $sql = "INSERT INTO reviews (booking_id, user_id, worker_id, rating, comment) VALUES (:booking_id, :user_id, :worker_id, :rating, :comment)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'booking_id' => $booking_id,
            'user_id' => $user_id,
            'worker_id' => $worker_id,
            'rating' => $rating,
            'comment' => $comment
        ]);
        
        // Notify worker about the review
        $worker = get_worker($worker_id);
        create_notification($worker['user_id'], 'New Review', 
            "You received a new $rating-star review!", 'review');
        
        return ['success' => true];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Failed to create review: ' . $e->getMessage()];
    }
}

// Get reviews for a worker
function get_worker_reviews($worker_id, $limit = 10) {
    global $conn;
    
    $sql = "SELECT r.*, u.name as reviewer_name, u.avatar as reviewer_avatar, b.title as booking_title
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            JOIN bookings b ON r.booking_id = b.id
            WHERE r.worker_id = :worker_id
            ORDER BY r.created_at DESC
            LIMIT :limit";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute(['worker_id' => $worker_id, 'limit' => $limit]);
    return $stmt->fetchAll();
}

// Get pending reviews for a user (completed bookings without reviews)
function get_pending_reviews($user_id) {
    global $conn;
    
    $sql = "SELECT b.*, wu.name as worker_name
            FROM bookings b
            JOIN workers w ON b.worker_id = w.id
            JOIN users wu ON w.user_id = wu.id
            LEFT JOIN reviews r ON b.id = r.booking_id
            WHERE b.user_id = :user_id AND b.status = 'completed' AND r.id IS NULL
            ORDER BY b.scheduled_date DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);
    return $stmt->fetchAll();
}

// ==================== NOTIFICATION FUNCTIONS ====================

// Create notification
function create_notification($user_id, $title, $message, $type = 'system', $link = null) {
    global $conn;
    
    try {
        $sql = "INSERT INTO notifications (user_id, title, message, type, link) VALUES (:user_id, :title, :message, :type, :link)";
        $stmt = $conn->prepare($sql);
        return $stmt->execute([
            'user_id' => $user_id,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'link' => $link
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

// Get user notifications
function get_notifications($user_id, $unread_only = false, $limit = 20) {
    global $conn;
    
    $sql = "SELECT * FROM notifications WHERE user_id = :user_id";
    $params = ['user_id' => $user_id];
    
    if ($unread_only) {
        $sql .= " AND is_read = FALSE";
    }
    $sql .= " ORDER BY created_at DESC LIMIT :limit";
    $params['limit'] = $limit;
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Count unread notifications
function count_unread_notifications($user_id) {
    global $conn;
    
    $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id AND is_read = FALSE";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);
    return $stmt->fetch()['count'];
}

// Mark notification as read
function mark_notification_read($notification_id) {
    global $conn;
    
    $sql = "UPDATE notifications SET is_read = TRUE WHERE id = :notification_id";
    $stmt = $conn->prepare($sql);
    return $stmt->execute(['notification_id' => $notification_id]);
}

// ==================== SERVICE CATEGORIES ====================

function get_service_categories() {
    global $conn;
    
    $sql = "SELECT * FROM service_categories ORDER BY name";
    $stmt = $conn->query($sql);
    return $stmt->fetchAll();
}

// ==================== FAVORITES ====================

function add_favorite($user_id, $worker_id) {
    global $conn;
    
    try {
        $sql = "INSERT INTO favorites (user_id, worker_id) VALUES (:user_id, :worker_id) ON CONFLICT DO NOTHING";
        $stmt = $conn->prepare($sql);
        return $stmt->execute(['user_id' => $user_id, 'worker_id' => $worker_id]);
    } catch (PDOException $e) {
        return false;
    }
}

function remove_favorite($user_id, $worker_id) {
    global $conn;
    
    $sql = "DELETE FROM favorites WHERE user_id = :user_id AND worker_id = :worker_id";
    $stmt = $conn->prepare($sql);
    return $stmt->execute(['user_id' => $user_id, 'worker_id' => $worker_id]);
}

function get_user_favorites($user_id) {
    global $conn;
    
    $sql = "SELECT w.*, u.name, u.avatar 
            FROM favorites f
            JOIN workers w ON f.worker_id = w.id
            JOIN users u ON w.user_id = u.id
            WHERE f.user_id = :user_id
            ORDER BY f.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);
    return $stmt->fetchAll();
}

function is_favorite($user_id, $worker_id) {
    global $conn;
    
    $sql = "SELECT id FROM favorites WHERE user_id = :user_id AND worker_id = :worker_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['user_id' => $user_id, 'worker_id' => $worker_id]);
    return $stmt->rowCount() > 0;
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
