<?php
require_once 'config.php';
require_once 'auth_functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = 'Please fill in all fields';
        header("Location: auth.html");
        exit();
    }
    
    $result = login_user($email, $password);
    
    if ($result['success']) {
        // Redirect based on user type
        if ($result['user_type'] === 'worker') {
            header("Location: worker.html");
        } else {
            header("Location: user.html");
        }
        exit();
    } else {
        $_SESSION['error'] = $result['message'];
        header("Location: auth.html");
        exit();
    }
}
?>