<?php
// views/layouts/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Bouncer para siguradong hindi makakapasok ang hindi naka-login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CHCC Finance System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
    
    <style>
        /* Pampabilis: I-set agad natin ang background sa maitim kung dark mode 
           para walang 1 millisecond na kislap ng puti */
        html.dark-loaded { background-color: #0f172a !important; }
    </style>
</head>
<body>
    <script>
        if (localStorage.getItem('financeTheme') === 'dark') {
            document.documentElement.classList.add('dark-loaded'); // Sa HTML tag
            document.body.classList.add('dark-mode');             // Sa BODY tag
        }
    </script>