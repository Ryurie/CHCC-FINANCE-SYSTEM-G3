<?php
session_start();
// Kung logged in na, i-redirect agad sa kanilang dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: views/" . $_SESSION['role'] . "/dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Finance & Fee Management System</title>
    <link rel="stylesheet" href="public/css/style.css">
    <style>
        .hero {
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: white;
        }
        .hero h1 { font-size: 3rem; margin-bottom: 1rem; }
        .hero p { font-size: 1.2rem; margin-bottom: 2rem; opacity: 0.9; }
    </style>
</head>
<body>
    <div class="hero">
        <h1>Finance & Fee System</h1>
        <p>A streamlined solution for student billing, payments, and clearance.</p>
        <div>
            <a href="views/auth/login.php" class="btn" style="background: white; color: #2563eb; padding: 1rem 2.5rem; font-weight: bold; text-decoration: none;">Get Started</a>
        </div>
    </div>
</body>
</html>