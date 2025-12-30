# ProTech - Professional Services Platform

A marketplace web application connecting clients with professional service workers (plumbers, electricians, cleaners, carpenters, etc.).

## Features

### For Clients (Users)
- Browse and search for professionals by skill, location, and rating
- Book services with specific date, time, and description
- Track booking status (pending, confirmed, in progress, completed)
- Leave reviews and ratings for completed services
- View booking history and statistics

### For Professionals (Workers)
- Receive and manage job requests
- Accept or decline booking requests
- View daily schedule and upcoming jobs
- Track earnings and completed jobs
- Build reputation through reviews and ratings

### Core Functionality
- User authentication (registration/login/logout)
- Two user types: clients and professional workers
- Real-time booking management
- Review and rating system
- Notification system

## Tech Stack

- **Frontend**: HTML5, CSS3, JavaScript (vanilla)
- **Backend**: PHP 8.x
- **Database**: MySQL/MariaDB
- **Session Management**: PHP Sessions

## Setup Instructions

### 1. Requirements
- PHP 8.0 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Web server (Apache, Nginx, or PHP's built-in server)

### 2. Database Setup

1. Create a MySQL database:
```sql
CREATE DATABASE protech_db;
```

2. Import the database schema:
```bash
mysql -u root -p protech_db < database.sql
```

Or manually run the SQL from `database.sql` in your MySQL client (phpMyAdmin, MySQL Workbench, etc.).

### 3. Configuration

Edit `config.php` to match your database settings:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
define('DB_NAME', 'protech_db');
```

### 4. Running the Application

#### Option A: Using PHP's Built-in Server
```bash
cd protech
php -S localhost:8000
```
Then open http://localhost:8000 in your browser.

#### Option B: Using XAMPP/WAMP/MAMP
1. Copy the `protech` folder to your web server's document root:
   - XAMPP: `C:\xampp\htdocs\protech`
   - WAMP: `C:\wamp64\www\protech`
   - MAMP: `/Applications/MAMP/htdocs/protech`
2. Start Apache and MySQL services
3. Open http://localhost/protech in your browser

### 5. First-Time Setup

1. Open the application in your browser
2. Click "Sign Up" to create an account
3. Choose between "User Signup" (client) or "Worker Signup" (professional)
4. Fill in the registration form and submit

## Project Structure

```
protech/
├── index.html              # Landing page
├── auth.html               # Login/Registration page
├── config.php              # Database configuration
├── auth_functions.php      # Authentication functions
├── db_functions.php        # Database CRUD functions
├── database.sql            # Database schema
│
├── User Pages:
│   ├── user.php            # User dashboard
│   ├── browse_workers.php  # Find professionals
│   ├── book_worker.php     # Book a service
│   ├── my_bookings.php     # View all bookings
│   └── review.php          # Leave a review
│
├── Worker Pages:
│   ├── worker.php          # Worker dashboard
│   └── job_requests.php    # Manage job requests
│
├── Handlers:
│   ├── login_handler.php       # Process login
│   ├── user_signup_handler.php # Process user registration
│   ├── worker_signup_handler.php # Process worker registration
│   ├── handle_booking.php      # Accept/decline/complete bookings
│   └── logout.php              # Process logout
│
└── php/                    # PHP runtime (bundled)
```

## Database Tables

| Table | Description |
|-------|-------------|
| `users` | All user accounts (clients and workers) |
| `workers` | Additional info for professional workers |
| `service_categories` | Types of services (Plumbing, Electrical, etc.) |
| `bookings` | Service bookings between users and workers |
| `reviews` | Ratings and reviews for completed services |
| `notifications` | User notifications |
| `favorites` | User's favorite workers |
| `messages` | Chat messages between users and workers |

## User Flows

### Client Flow
1. Register as a user → Dashboard
2. Browse workers by skill/location → Book a worker
3. Wait for worker to accept → Service confirmed
4. Worker completes service → Leave a review

### Worker Flow
1. Register as a worker → Dashboard
2. Receive job requests → Accept/Decline
3. View daily schedule → Complete jobs
4. Earn money and build reputation

## Sample Data

To add sample data for testing, you can create workers and bookings manually or run:

```sql
-- Sample worker
INSERT INTO users (name, email, phone, password, user_type) 
VALUES ('Mike Johnson', 'mike@example.com', '+1234567890', 
        '$2y$10$...hashed_password...', 'worker');

INSERT INTO workers (user_id, service_area, skills, experience, hourly_rate, rating) 
VALUES (LAST_INSERT_ID(), 'New York', 'Plumbing, Pipe Repair', 5, 75.00, 4.8);
```

## Security Notes

- Passwords are hashed using PHP's `password_hash()` with bcrypt
- All user inputs are sanitized to prevent SQL injection
- Sessions are used for authentication
- CSRF protection should be added for production use

## License

This project is for educational/demonstration purposes.

