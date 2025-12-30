<?php
require_once 'config.php';
require_once 'auth_functions.php';
require_once 'db_functions.php';

// Require user to be logged in as 'worker'
require_user_type('worker');

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];

// Get worker info
$worker = get_worker_by_user_id($user_id);
$worker_id = $worker['id'];

// Get worker statistics
$stats = get_worker_stats($worker_id);

// Get pending job requests
$pending_requests = get_worker_bookings($worker_id, 'pending', 5);

// Get today's schedule
$today_schedule = get_worker_today_schedule($worker_id);

// Get recent completed jobs
$recent_completed = get_worker_bookings($worker_id, 'completed', 5);

// Get notifications count
$unread_notifications = count_unread_notifications($user_id);

// Get user initials for avatar
$initials = get_initials($user_name);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProTech - Worker Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

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
            color: #10b981;
            text-decoration: none;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-icon svg {
            width: 24px;
            height: 24px;
            fill: white;
        }

        .top-nav-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .icon-btn {
            position: relative;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 8px;
            transition: background 0.3s;
        }

        .icon-btn:hover {
            background: #f3f4f6;
        }

        .icon-btn svg {
            width: 24px;
            height: 24px;
            stroke: #6b7280;
            fill: none;
            stroke-width: 2;
        }

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
            border: 2px solid white;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .user-menu:hover {
            background: #f3f4f6;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .user-info {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 600;
            font-size: 0.875rem;
        }

        .user-role {
            font-size: 0.75rem;
            color: #10b981;
        }

        .dashboard-container {
            display: flex;
            min-height: calc(100vh - 73px);
        }

        .sidebar {
            width: 260px;
            background: white;
            border-right: 1px solid #e5e7eb;
            padding: 1.5rem 0;
        }

        .sidebar-section {
            margin-bottom: 2rem;
        }

        .sidebar-title {
            padding: 0 1.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            color: #9ca3af;
            margin-bottom: 0.5rem;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-item {
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.3s;
            border-left: 3px solid transparent;
            text-decoration: none;
        }

        .sidebar-item:hover {
            background: #f9fafb;
            color: #10b981;
        }

        .sidebar-item.active {
            background: #d1fae5;
            color: #10b981;
            border-left-color: #10b981;
            font-weight: 600;
        }

        .sidebar-item svg {
            width: 20px;
            height: 20px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
        }

        .sidebar-badge {
            margin-left: auto;
            background: #ef4444;
            color: white;
            font-size: 0.7rem;
            font-weight: 600;
            padding: 0.125rem 0.5rem;
            border-radius: 10px;
        }

        .main-content {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: #6b7280;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            transition: all 0.3s;
        }

        .stat-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .stat-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-icon svg {
            width: 24px;
            height: 24px;
            stroke: white;
            fill: none;
            stroke-width: 2;
        }

        .stat-icon.green { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .stat-icon.blue { background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%); }
        .stat-icon.orange { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        .stat-icon.purple { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); }

        .stat-info h3 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .stat-info p {
            color: #6b7280;
            font-size: 0.875rem;
        }

        .stat-change {
            font-size: 0.75rem;
            margin-top: 0.5rem;
            display: inline-block;
        }

        .stat-change.positive {
            color: #10b981;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }

        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
        }

        .card-link {
            color: #10b981;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .card-link:hover {
            color: #059669;
        }

        .card-body {
            padding: 1.5rem;
        }

        .job-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .job-item {
            padding: 1.25rem;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .job-item:hover {
            border-color: #10b981;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.1);
        }

        .job-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .job-title {
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 0.25rem;
        }

        .job-client {
            color: #6b7280;
            font-size: 0.875rem;
        }

        .job-price {
            font-size: 1.25rem;
            font-weight: 700;
            color: #10b981;
        }

        .job-details {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .job-detail {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6b7280;
            font-size: 0.875rem;
        }

        .job-detail svg {
            width: 16px;
            height: 16px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
        }

        .job-description {
            color: #6b7280;
            font-size: 0.875rem;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .job-actions {
            display: flex;
            gap: 0.75rem;
        }

        .btn {
            padding: 0.625rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.3s;
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .btn-outline {
            background: transparent;
            color: #6b7280;
            border: 1px solid #d1d5db;
        }

        .btn-outline:hover {
            background: #f9fafb;
            border-color: #9ca3af;
        }

        .schedule-item {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .schedule-time {
            min-width: 80px;
            text-align: center;
            padding: 0.5rem;
            background: #d1fae5;
            border-radius: 6px;
        }

        .schedule-time-hour {
            font-size: 1.125rem;
            font-weight: 700;
            color: #065f46;
        }

        .schedule-time-period {
            font-size: 0.75rem;
            color: #059669;
        }

        .schedule-info {
            flex: 1;
        }

        .schedule-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .schedule-client {
            color: #6b7280;
            font-size: 0.875rem;
        }

        .schedule-location {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6b7280;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }

        .schedule-location svg {
            width: 14px;
            height: 14px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
        }

        .empty-state {
            text-align: center;
            padding: 2rem 1rem;
            color: #6b7280;
        }

        .empty-state svg {
            width: 48px;
            height: 48px;
            margin: 0 auto 1rem;
            stroke: #d1d5db;
            fill: none;
            stroke-width: 1.5;
        }

        .empty-state h3 {
            font-size: 1rem;
            color: #111827;
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            font-size: 0.875rem;
        }

        .rating-stars {
            color: #f59e0b;
        }

        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .main-content {
                padding: 1rem;
            }
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
                <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                <?php if ($unread_notifications > 0): ?>
                <span class="notification-badge"><?php echo $unread_notifications; ?></span>
                <?php endif; ?>
            </a>
            <a href="messages.php" class="icon-btn">
                <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            </a>
            <div class="user-menu">
                <div class="user-avatar"><?php echo htmlspecialchars($initials); ?></div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($user_name); ?></div>
                    <div class="user-role">Professional</div>
                </div>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-section">
                <div class="sidebar-title">Main Menu</div>
                <ul class="sidebar-menu">
                    <li><a href="worker.php" class="sidebar-item active">
                        <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        Dashboard
                    </a></li>
                    <li><a href="job_requests.php" class="sidebar-item">
                        <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        Job Requests
                        <?php if ($stats['pending_requests'] > 0): ?>
                        <span class="sidebar-badge"><?php echo $stats['pending_requests']; ?></span>
                        <?php endif; ?>
                    </a></li>
                    <li><a href="my_schedule.php" class="sidebar-item">
                        <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        My Schedule
                    </a></li>
                    <li><a href="messages.php" class="sidebar-item">
                        <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        Messages
                    </a></li>
                    <li><a href="earnings.php" class="sidebar-item">
                        <svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        Earnings
                    </a></li>
                    <li><a href="my_reviews.php" class="sidebar-item">
                        <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        Reviews
                    </a></li>
                </ul>
            </div>
            <div class="sidebar-section">
                <div class="sidebar-title">Account</div>
                <ul class="sidebar-menu">
                    <li><a href="profile.php" class="sidebar-item">
                        <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        My Profile
                    </a></li>
                    <li><a href="availability.php" class="sidebar-item">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        Availability
                    </a></li>
                    <li><a href="settings.php" class="sidebar-item">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M12 1v6m0 6v6"/></svg>
                        Settings
                    </a></li>
                    <li><a href="logout.php" class="sidebar-item">
                        <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        Logout
                    </a></li>
                </ul>
            </div>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h1>Welcome back, <?php echo htmlspecialchars(explode(' ', $user_name)[0]); ?>! 💼</h1>
                <p>Here's an overview of your professional activities.</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon green">
                            <svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo format_price($stats['monthly_earnings']); ?></h3>
                            <p>This Month</p>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon blue">
                            <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['pending_requests']; ?></h3>
                            <p>Pending Requests</p>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon orange">
                            <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['scheduled_jobs']; ?></h3>
                            <p>Scheduled Jobs</p>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon purple">
                            <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['rating'], 1); ?></h3>
                            <p>Rating (<?php echo $stats['total_reviews']; ?> reviews)</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-grid">
                <div class="card">
                    <div class="card-header">
                        <h2>New Job Requests</h2>
                        <a href="job_requests.php" class="card-link">View All →</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pending_requests)): ?>
                        <div class="empty-state">
                            <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                            <h3>No pending requests</h3>
                            <p>New job requests will appear here</p>
                        </div>
                        <?php else: ?>
                        <div class="job-list">
                            <?php foreach ($pending_requests as $job): ?>
                            <div class="job-item">
                                <div class="job-header">
                                    <div>
                                        <div class="job-title"><?php echo htmlspecialchars($job['title']); ?></div>
                                        <div class="job-client"><?php echo htmlspecialchars($job['client_name']); ?></div>
                                    </div>
                                    <div class="job-price"><?php echo format_price($job['price']); ?></div>
                                </div>
                                <div class="job-details">
                                    <div class="job-detail">
                                        <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                        <?php echo format_date($job['scheduled_date']); ?>
                                    </div>
                                    <div class="job-detail">
                                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                        <?php echo format_time($job['scheduled_time']); ?>
                                    </div>
                                    <div class="job-detail">
                                        <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                        <?php echo htmlspecialchars(substr($job['address'], 0, 30)); ?>...
                                    </div>
                                </div>
                                <?php if (!empty($job['description'])): ?>
                                <div class="job-description">
                                    <?php echo htmlspecialchars(substr($job['description'], 0, 100)); ?>...
                                </div>
                                <?php endif; ?>
                                <div class="job-actions">
                                    <form action="handle_booking.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="booking_id" value="<?php echo $job['id']; ?>">
                                        <input type="hidden" name="action" value="accept">
                                        <button type="submit" class="btn btn-success">Accept</button>
                                    </form>
                                    <form action="handle_booking.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="booking_id" value="<?php echo $job['id']; ?>">
                                        <input type="hidden" name="action" value="decline">
                                        <button type="submit" class="btn btn-outline">Decline</button>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2>Today's Schedule</h2>
                    </div>
                    <div class="card-body">
                        <?php if (empty($today_schedule)): ?>
                        <div class="empty-state">
                            <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <h3>No jobs scheduled for today</h3>
                            <p>Your schedule is clear!</p>
                        </div>
                        <?php else: ?>
                        <?php foreach ($today_schedule as $schedule): ?>
                        <div class="schedule-item">
                            <div class="schedule-time">
                                <div class="schedule-time-hour"><?php echo date('g:i', strtotime($schedule['scheduled_time'])); ?></div>
                                <div class="schedule-time-period"><?php echo date('A', strtotime($schedule['scheduled_time'])); ?></div>
                            </div>
                            <div class="schedule-info">
                                <div class="schedule-title"><?php echo htmlspecialchars($schedule['title']); ?></div>
                                <div class="schedule-client"><?php echo htmlspecialchars($schedule['client_name']); ?></div>
                                <div class="schedule-location">
                                    <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                    <?php echo htmlspecialchars($schedule['address']); ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Completions -->
            <?php if (!empty($recent_completed)): ?>
            <div class="card">
                <div class="card-header">
                    <h2>Recent Completions</h2>
                    <a href="my_schedule.php?status=completed" class="card-link">View All →</a>
                </div>
                <div class="card-body">
                    <div class="job-list">
                        <?php foreach ($recent_completed as $job): ?>
                        <div class="job-item">
                            <div class="job-header">
                                <div>
                                    <div class="job-title"><?php echo htmlspecialchars($job['title']); ?></div>
                                    <div class="job-client"><?php echo htmlspecialchars($job['client_name']); ?> • Completed <?php echo format_date($job['scheduled_date']); ?></div>
                                </div>
                                <div class="job-price"><?php echo format_price($job['price']); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
