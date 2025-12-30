<?php
require_once 'config.php';
require_once 'auth_functions.php';
require_once 'db_functions.php';

require_user_type('user');

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$initials = get_initials($user_name);

// Get status filter
$status_filter = $_GET['status'] ?? null;

// Get all bookings for user
$bookings = get_user_bookings($user_id, $status_filter);

// Messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

$unread_notifications = count_unread_notifications($user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - ProTech</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f9fafb;
            color: #111827;
        }
        .top-nav {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
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
        .top-nav-right { display: flex; align-items: center; gap: 1.5rem; }
        .icon-btn {
            position: relative;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 8px;
            text-decoration: none;
        }
        .icon-btn:hover { background: #f3f4f6; }
        .icon-btn svg { width: 24px; height: 24px; stroke: #6b7280; fill: none; stroke-width: 2; }
        .notification-badge {
            position: absolute;
            top: 0;
            right: 0;
            min-width: 18px;
            height: 18px;
            background: #ef4444;
            border-radius: 50%;
            color: white;
            font-size: 0.7rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .user-menu {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            color: inherit;
        }
        .user-menu:hover { background: #f3f4f6; }
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
        }
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .page-header h1 {
            font-size: 2rem;
        }
        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            background: white;
            padding: 0.5rem;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        .filter-tab {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            color: #6b7280;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.3s;
        }
        .filter-tab:hover { background: #f3f4f6; color: #111827; }
        .filter-tab.active { background: #2563eb; color: white; }
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .bookings-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .booking-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            padding: 1.5rem;
            transition: all 0.3s;
        }
        .booking-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }
        .booking-main {
            display: flex;
            gap: 1rem;
        }
        .worker-avatar {
            width: 56px;
            height: 56px;
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
        .booking-info h3 {
            font-size: 1.125rem;
            margin-bottom: 0.25rem;
        }
        .booking-info p {
            color: #6b7280;
            font-size: 0.875rem;
        }
        .booking-status {
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .booking-status.pending { background: #fef3c7; color: #92400e; }
        .booking-status.confirmed { background: #d1fae5; color: #065f46; }
        .booking-status.in_progress { background: #e0e7ff; color: #3730a3; }
        .booking-status.completed { background: #dbeafe; color: #1e40af; }
        .booking-status.cancelled { background: #fee2e2; color: #991b1b; }
        .booking-details {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 1rem;
            padding: 1rem;
            background: #f9fafb;
            border-radius: 8px;
        }
        .booking-detail {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6b7280;
            font-size: 0.875rem;
        }
        .booking-detail svg {
            width: 16px;
            height: 16px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
        }
        .booking-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
        }
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            font-size: 0.875rem;
            text-decoration: none;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
            color: white;
        }
        .btn-primary:hover {
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
        }
        .btn-outline {
            background: transparent;
            color: #6b7280;
            border: 1px solid #d1d5db;
        }
        .btn-outline:hover {
            background: #f9fafb;
        }
        .btn-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .btn-danger:hover {
            background: #fecaca;
        }
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
        }
        .empty-state svg {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            stroke: #d1d5db;
            fill: none;
            stroke-width: 1.5;
        }
        .empty-state h3 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }
        .empty-state p {
            color: #6b7280;
            margin-bottom: 1.5rem;
        }
        @media (max-width: 768px) {
            .page-header { flex-direction: column; gap: 1rem; align-items: flex-start; }
            .filter-tabs { flex-wrap: wrap; }
            .booking-header { flex-direction: column; gap: 1rem; }
            .booking-details { flex-direction: column; gap: 0.75rem; }
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
        <div class="top-nav-right">
            <a href="notifications.php" class="icon-btn">
                <svg viewBox="0 0 24 24">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                <?php if ($unread_notifications > 0): ?>
                <span class="notification-badge"><?php echo $unread_notifications; ?></span>
                <?php endif; ?>
            </a>
            <a href="user.php" class="user-menu">
                <div class="user-avatar"><?php echo htmlspecialchars($initials); ?></div>
            </a>
        </div>
    </nav>

    <div class="main-container">
        <div class="page-header">
            <h1>My Bookings</h1>
            <div class="filter-tabs">
                <a href="my_bookings.php" class="filter-tab <?php echo !$status_filter ? 'active' : ''; ?>">All</a>
                <a href="my_bookings.php?status=pending" class="filter-tab <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">Pending</a>
                <a href="my_bookings.php?status=confirmed" class="filter-tab <?php echo $status_filter === 'confirmed' ? 'active' : ''; ?>">Confirmed</a>
                <a href="my_bookings.php?status=completed" class="filter-tab <?php echo $status_filter === 'completed' ? 'active' : ''; ?>">Completed</a>
                <a href="my_bookings.php?status=cancelled" class="filter-tab <?php echo $status_filter === 'cancelled' ? 'active' : ''; ?>">Cancelled</a>
            </div>
        </div>

        <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (empty($bookings)): ?>
        <div class="empty-state">
            <svg viewBox="0 0 24 24">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            <h3>No bookings found</h3>
            <p>You haven't made any bookings yet. Find a professional to get started!</p>
            <a href="browse_workers.php" class="btn btn-primary">Find Professionals</a>
        </div>
        <?php else: ?>
        <div class="bookings-list">
            <?php foreach ($bookings as $booking): ?>
            <div class="booking-card">
                <div class="booking-header">
                    <div class="booking-main">
                        <div class="worker-avatar"><?php echo get_initials($booking['worker_name']); ?></div>
                        <div class="booking-info">
                            <h3><?php echo htmlspecialchars($booking['title']); ?></h3>
                            <p><?php echo htmlspecialchars($booking['worker_name']); ?> • <?php echo htmlspecialchars($booking['service_name'] ?? 'General Service'); ?></p>
                        </div>
                    </div>
                    <span class="booking-status <?php echo $booking['status']; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $booking['status'])); ?>
                    </span>
                </div>
                
                <div class="booking-details">
                    <div class="booking-detail">
                        <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <?php echo format_date($booking['scheduled_date']); ?>
                    </div>
                    <div class="booking-detail">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <?php echo format_time($booking['scheduled_time']); ?>
                    </div>
                    <div class="booking-detail">
                        <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        <?php echo htmlspecialchars($booking['address']); ?>
                    </div>
                    <div class="booking-detail">
                        <svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        <?php echo format_price($booking['price']); ?>
                    </div>
                </div>
                
                <div class="booking-actions">
                    <?php if ($booking['status'] === 'completed'): ?>
                        <a href="review.php?booking_id=<?php echo $booking['id']; ?>" class="btn btn-primary">Leave Review</a>
                    <?php elseif (in_array($booking['status'], ['pending', 'confirmed'])): ?>
                        <form action="handle_booking.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                            <input type="hidden" name="action" value="cancel">
                            <button type="submit" class="btn btn-danger">Cancel Booking</button>
                        </form>
                    <?php endif; ?>
                    <a href="messages.php?booking_id=<?php echo $booking['id']; ?>" class="btn btn-outline">Message</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>

