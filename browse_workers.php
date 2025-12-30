<?php
require_once 'config.php';
require_once 'auth_functions.php';
require_once 'db_functions.php';

require_user_type('user');

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$initials = get_initials($user_name);

// Get filters from query string
$filters = [];
if (!empty($_GET['search'])) {
    $filters['skill'] = $_GET['search'];
}
if (!empty($_GET['area'])) {
    $filters['service_area'] = $_GET['area'];
}
if (!empty($_GET['rating'])) {
    $filters['min_rating'] = floatval($_GET['rating']);
}
$filters['is_available'] = true;

// Get workers
$workers = get_workers($filters);

// Get service categories for filter
$categories = get_service_categories();

$unread_notifications = count_unread_notifications($user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProTech - Find Professionals</title>
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
            transition: background 0.3s;
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
            cursor: pointer;
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
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        .page-header {
            margin-bottom: 2rem;
        }
        .page-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .page-header p { color: #6b7280; }
        .search-filters {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            margin-bottom: 2rem;
        }
        .search-form {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .search-input {
            flex: 2;
            min-width: 250px;
        }
        .filter-input {
            flex: 1;
            min-width: 150px;
        }
        .search-input input,
        .filter-input select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 1rem;
            outline: none;
        }
        .search-input input:focus,
        .filter-input select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
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
        .workers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }
        .worker-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            transition: all 0.3s;
        }
        .worker-card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transform: translateY(-5px);
        }
        .worker-header {
            padding: 1.5rem;
            display: flex;
            gap: 1rem;
        }
        .worker-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.5rem;
            flex-shrink: 0;
        }
        .worker-info { flex: 1; }
        .worker-name {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        .worker-skills {
            color: #6b7280;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }
        .worker-rating {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }
        .stars { color: #f59e0b; }
        .rating-count { color: #6b7280; }
        .worker-details {
            padding: 0 1.5rem 1.5rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6b7280;
            font-size: 0.875rem;
        }
        .detail-item svg {
            width: 16px;
            height: 16px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
        }
        .worker-footer {
            padding: 1rem 1.5rem;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .worker-price {
            font-size: 1.25rem;
            font-weight: 700;
            color: #10b981;
        }
        .worker-price span {
            font-size: 0.875rem;
            font-weight: 400;
            color: #6b7280;
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
        }
        @media (max-width: 768px) {
            .search-form { flex-direction: column; }
            .search-input, .filter-input { min-width: 100%; }
            .workers-grid { grid-template-columns: 1fr; }
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
            <h1>Find Professionals</h1>
            <p>Browse and book trusted professionals for your needs</p>
        </div>

        <div class="search-filters">
            <form class="search-form" method="GET">
                <div class="search-input">
                    <input type="text" name="search" placeholder="Search by skill (e.g., Plumbing, Electrical...)" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                </div>
                <div class="filter-input">
                    <select name="area">
                        <option value="">All Locations</option>
                        <option value="New York" <?php echo ($_GET['area'] ?? '') === 'New York' ? 'selected' : ''; ?>>New York</option>
                        <option value="Los Angeles" <?php echo ($_GET['area'] ?? '') === 'Los Angeles' ? 'selected' : ''; ?>>Los Angeles</option>
                        <option value="Chicago" <?php echo ($_GET['area'] ?? '') === 'Chicago' ? 'selected' : ''; ?>>Chicago</option>
                        <option value="Houston" <?php echo ($_GET['area'] ?? '') === 'Houston' ? 'selected' : ''; ?>>Houston</option>
                        <option value="Yekaterinburg" <?php echo ($_GET['area'] ?? '') === 'Yekaterinburg' ? 'selected' : ''; ?>>Yekaterinburg</option>
                    </select>
                </div>
                <div class="filter-input">
                    <select name="rating">
                        <option value="">Any Rating</option>
                        <option value="4" <?php echo ($_GET['rating'] ?? '') === '4' ? 'selected' : ''; ?>>4+ Stars</option>
                        <option value="4.5" <?php echo ($_GET['rating'] ?? '') === '4.5' ? 'selected' : ''; ?>>4.5+ Stars</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>

        <?php if (empty($workers)): ?>
        <div class="empty-state">
            <svg viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="8"/>
                <path d="m21 21-4.35-4.35"/>
            </svg>
            <h3>No professionals found</h3>
            <p>Try adjusting your search filters or check back later</p>
        </div>
        <?php else: ?>
        <div class="workers-grid">
            <?php foreach ($workers as $worker): ?>
            <div class="worker-card">
                <div class="worker-header">
                    <div class="worker-avatar"><?php echo get_initials($worker['name']); ?></div>
                    <div class="worker-info">
                        <div class="worker-name"><?php echo htmlspecialchars($worker['name']); ?></div>
                        <div class="worker-skills"><?php echo htmlspecialchars($worker['skills']); ?></div>
                        <div class="worker-rating">
                            <span class="stars">
                                <?php 
                                $rating = $worker['rating'];
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $rating ? '★' : '☆';
                                }
                                ?>
                            </span>
                            <span><?php echo number_format($worker['rating'], 1); ?></span>
                            <span class="rating-count">(<?php echo $worker['total_reviews']; ?> reviews)</span>
                        </div>
                    </div>
                </div>
                <div class="worker-details">
                    <div class="detail-item">
                        <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        <?php echo htmlspecialchars($worker['service_area']); ?>
                    </div>
                    <div class="detail-item">
                        <svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                        <?php echo $worker['experience']; ?> years exp.
                    </div>
                    <div class="detail-item">
                        <svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <?php echo $worker['total_jobs']; ?> jobs done
                    </div>
                </div>
                <div class="worker-footer">
                    <div class="worker-price">
                        <?php echo format_price($worker['hourly_rate']); ?><span>/hr</span>
                    </div>
                    <a href="book_worker.php?worker_id=<?php echo $worker['id']; ?>" class="btn btn-primary">Book Now</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>

