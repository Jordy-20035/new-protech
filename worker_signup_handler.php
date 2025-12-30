<?php
require_once 'config.php';
require_once 'auth_functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
    $service_area = $_POST['address'] ?? '';
    $skills = $_POST['skills'] ?? '';
    $experience = $_POST['experience'] ?? 0;
    
    if (empty($name) || empty($email) || empty($phone) || empty($password) || 
        empty($service_area) || empty($skills) || empty($experience)) {
        $_SESSION['error'] = 'Please fill in all fields';
        header("Location: auth.html");
        exit();
    }
    
    $result = register_worker($name, $email, $phone, $password, $service_area, $skills, $experience);
    
    if ($result['success']) {
        $_SESSION['success'] = 'Worker registration successful! Please login.';
        header("Location: auth.html");
    } else {
        $_SESSION['error'] = $result['message'];
        header("Location: auth.html");
    }
    exit();
}
?>