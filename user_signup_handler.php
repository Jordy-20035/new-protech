<?php
require_once 'config.php';
require_once 'auth_functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? 'Manye Patrice';
    $email = $_POST['email'] ?? 'patrice@protech.com';
    $phone = $_POST['phone'] ?? '+79010308633';
    $password = $_POST['password'] ?? 'user1234';
    
    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        $_SESSION['error'] = 'Please fill in all fields';
        header("Location: auth.html");
        exit();
    }
    
    $result = register_user($name, $email, $phone, $password, 'user');
    
    if ($result['success']) {
        $_SESSION['success'] = 'Registration successful! Please login.';
        header("Location: auth.html");
    } else {
        $_SESSION['error'] = $result['message'];
        header("Location: auth.html");
    }
    exit();
}
?>