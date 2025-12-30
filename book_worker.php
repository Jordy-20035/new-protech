<?php
require_once 'config.php';
require_once 'auth_functions.php';
require_once 'db_functions.php';

require_user_type('user');

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$initials = get_initials($user_name);

// Get worker ID from query string
$worker_id = isset($_GET['worker_id']) ? intval($_GET['worker_id']) : 0;

if ($worker_id <= 0) {
    header("Location: browse_workers.php");
    exit();
}

// Get worker details
$worker = get_worker($worker_id);
if (!$worker) {
    header("Location: browse_workers.php");
    exit();
}

// Get service categories
$categories = get_service_categories();

// Handle form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $scheduled_date = $_POST['scheduled_date'] ?? '';
    $scheduled_time = $_POST['scheduled_time'] ?? '';
    $address = trim($_POST['address'] ?? '');
    $service_category_id = intval($_POST['service_category_id'] ?? 0);
    $estimated_duration = intval($_POST['estimated_duration'] ?? 60);
    
    // Calculate price based on hourly rate and duration
    $price = ($worker['hourly_rate'] / 60) * $estimated_duration;
    
    if (empty($title) || empty($scheduled_date) || empty($scheduled_time) || empty($address)) {
        $error = 'Please fill in all required fields';
    } else {
        $data = [
            'title' => $title,
            'description' => $description,
            'scheduled_date' => $scheduled_date,
            'scheduled_time' => $scheduled_time,
            'address' => $address,
            'service_category_id' => $service_category_id ?: null,
            'estimated_duration' => $estimated_duration,
            'price' => $price
        ];
        
        $result = create_booking($user_id, $worker_id, $data);
        
        if ($result['success']) {
            $success = 'Booking request sent successfully! The professional will review your request.';
            // Redirect after 2 seconds
            header("Refresh: 2; url=my_bookings.php");
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
    <title>Book <?php echo htmlspecialchars($worker['name']); ?> - ProTech</title>
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
        .main-container {
            max-width: 1000px;
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
        .booking-grid {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 2rem;
        }
        .booking-form-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            padding: 2rem;
        }
        .booking-form-card h1 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
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
        .form-group label span {
            color: #ef4444;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 1rem;
            outline: none;
            font-family: inherit;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
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
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .btn {
            padding: 0.875rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s;
            width: 100%;
        }
        .btn-primary {
            background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        .worker-summary {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            padding: 1.5rem;
            position: sticky;
            top: 100px;
        }
        .worker-summary h3 {
            font-size: 1rem;
            color: #6b7280;
            margin-bottom: 1rem;
        }
        .worker-profile {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
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
        .worker-name {
            font-size: 1.125rem;
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
            gap: 0.25rem;
            font-size: 0.875rem;
        }
        .stars { color: #f59e0b; }
        .price-summary {
            margin-bottom: 1.5rem;
        }
        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            font-size: 0.875rem;
        }
        .price-row.total {
            font-size: 1.125rem;
            font-weight: 600;
            padding-top: 0.75rem;
            border-top: 1px solid #e5e7eb;
            margin-top: 0.75rem;
        }
        .price-row .label { color: #6b7280; }
        .price-row .value { font-weight: 500; }
        .info-note {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 1rem;
            font-size: 0.875rem;
            color: #1e40af;
        }
        .info-note svg {
            width: 16px;
            height: 16px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
            vertical-align: middle;
            margin-right: 0.5rem;
        }
        @media (max-width: 768px) {
            .booking-grid {
                grid-template-columns: 1fr;
            }
            .worker-summary {
                position: static;
            }
            .form-row {
                grid-template-columns: 1fr;
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
    </nav>

    <div class="main-container">
        <a href="browse_workers.php" class="back-link">
            <svg viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></svg>
            Back to professionals
        </a>

        <div class="booking-grid">
            <div class="booking-form-card">
                <h1>Book Service</h1>
                
                <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php else: ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Service Title <span>*</span></label>
                        <input type="text" name="title" placeholder="e.g., Kitchen Sink Repair" required value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Service Category</label>
                        <select name="service_category_id">
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="4" placeholder="Describe what you need help with..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Preferred Date <span>*</span></label>
                            <input type="date" name="scheduled_date" required min="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($_POST['scheduled_date'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Preferred Time <span>*</span></label>
                            <input type="time" name="scheduled_time" required value="<?php echo htmlspecialchars($_POST['scheduled_time'] ?? '09:00'); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Estimated Duration (minutes)</label>
                        <select name="estimated_duration" id="duration-select">
                            <option value="30">30 minutes</option>
                            <option value="60" selected>1 hour</option>
                            <option value="90">1.5 hours</option>
                            <option value="120">2 hours</option>
                            <option value="180">3 hours</option>
                            <option value="240">4 hours</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Service Address <span>*</span></label>
                        <input type="text" name="address" placeholder="Enter your full address" required value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Send Booking Request</button>
                </form>
                <?php endif; ?>
            </div>

            <div class="worker-summary">
                <h3>Professional</h3>
                <div class="worker-profile">
                    <div class="worker-avatar"><?php echo get_initials($worker['name']); ?></div>
                    <div>
                        <div class="worker-name"><?php echo htmlspecialchars($worker['name']); ?></div>
                        <div class="worker-skills"><?php echo htmlspecialchars($worker['skills']); ?></div>
                        <div class="worker-rating">
                            <span class="stars">★</span>
                            <span><?php echo number_format($worker['rating'], 1); ?></span>
                            <span style="color:#6b7280">(<?php echo $worker['total_reviews']; ?>)</span>
                        </div>
                    </div>
                </div>
                
                <div class="price-summary">
                    <div class="price-row">
                        <span class="label">Hourly Rate</span>
                        <span class="value"><?php echo format_price($worker['hourly_rate']); ?>/hr</span>
                    </div>
                    <div class="price-row">
                        <span class="label">Duration</span>
                        <span class="value" id="duration-display">1 hour</span>
                    </div>
                    <div class="price-row total">
                        <span class="label">Estimated Total</span>
                        <span class="value" id="total-price"><?php echo format_price($worker['hourly_rate']); ?></span>
                    </div>
                </div>
                
                <div class="info-note">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                    The professional will review your request and confirm availability.
                </div>
            </div>
        </div>
    </div>

    <script>
        const hourlyRate = <?php echo $worker['hourly_rate']; ?>;
        const durationSelect = document.getElementById('duration-select');
        const durationDisplay = document.getElementById('duration-display');
        const totalPrice = document.getElementById('total-price');
        
        durationSelect.addEventListener('change', function() {
            const minutes = parseInt(this.value);
            const hours = minutes / 60;
            
            if (hours < 1) {
                durationDisplay.textContent = minutes + ' minutes';
            } else if (hours === 1) {
                durationDisplay.textContent = '1 hour';
            } else {
                durationDisplay.textContent = hours + ' hours';
            }
            
            const price = (hourlyRate / 60) * minutes;
            totalPrice.textContent = '$' + price.toFixed(2);
        });
    </script>
</body>
</html>

