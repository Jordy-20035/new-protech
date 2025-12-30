<?php
require_once 'config.php';

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to register a new user
function register_user($name, $email, $phone, $password, $user_type) {
    global $conn;
    
    $name = sanitize_input($name);
    $email = sanitize_input($email);
    $phone = sanitize_input($phone);
    $user_type = sanitize_input($user_type);
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        // Check if email already exists
        $check_email = "SELECT id FROM users WHERE email = :email";
        $stmt = $conn->prepare($check_email);
        $stmt->execute(['email' => $email]);
        
        if ($stmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'Email already exists'];
        }
        
        // Insert user
        $sql = "INSERT INTO users (name, email, phone, password, user_type) VALUES (:name, :email, :phone, :password, :user_type)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => $hashed_password,
            'user_type' => $user_type
        ]);
        
        $user_id = $conn->lastInsertId();
        return ['success' => true, 'user_id' => $user_id, 'message' => 'Registration successful'];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
    }
}

// Function to register a worker with additional info
function register_worker($name, $email, $phone, $password, $service_area, $skills, $experience) {
    global $conn;
    
    // First register as user
    $result = register_user($name, $email, $phone, $password, 'worker');
    
    if ($result['success']) {
        $user_id = $result['user_id'];
        $service_area = sanitize_input($service_area);
        $skills = sanitize_input($skills);
        $experience = intval($experience);
        
        try {
            // Insert worker details
            $sql = "INSERT INTO workers (user_id, service_area, skills, experience) VALUES (:user_id, :service_area, :skills, :experience)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'user_id' => $user_id,
                'service_area' => $service_area,
                'skills' => $skills,
                'experience' => $experience
            ]);
            
            return ['success' => true, 'message' => 'Worker registration successful'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Worker details registration failed: ' . $e->getMessage()];
        }
    }
    
    return $result;
}

// Function to login user
function login_user($email, $password) {
    global $conn;
    
    $email = sanitize_input($email);
    
    try {
        $sql = "SELECT id, name, email, password, user_type FROM users WHERE email = :email";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['email' => $email]);
        
        if ($stmt->rowCount() === 1) {
            $user = $stmt->fetch();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['logged_in'] = true;
                
                return [
                    'success' => true, 
                    'user_type' => $user['user_type'],
                    'message' => 'Login successful'
                ];
            } else {
                return ['success' => false, 'message' => 'Invalid password'];
            }
        } else {
            return ['success' => false, 'message' => 'User not found'];
        }
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Login failed: ' . $e->getMessage()];
    }
}

// Function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

// Function to check user type
function get_user_type() {
    return isset($_SESSION['user_type']) ? $_SESSION['user_type'] : null;
}

// Function to logout
function logout_user() {
    session_unset();
    session_destroy();
    return ['success' => true, 'message' => 'Logged out successfully'];
}

// Function to require login
function require_login() {
    if (!is_logged_in()) {
        header("Location: auth.html");
        exit();
    }
}

// Function to require specific user type
function require_user_type($type) {
    require_login();
    if (get_user_type() !== $type) {
        header("Location: auth.html");
        exit();
    }
}
?>
