<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: ../" . $_SESSION['role'] . "/dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Finance & Fee System</title>
    <link rel="stylesheet" href="../../public/css/style.css">
    <link rel="stylesheet" href="../../public/css/forms.css">
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="auth-card">
            <h2>Create Account</h2>
            <p>Join the Finance & Fee Management System</p>

            <div id="regMessage" class="alert" style="display: none;"></div>

            <form id="registerForm">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" id="name" required placeholder="Juan Dela Cruz">
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" id="email" required placeholder="email@example.com">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" id="password" required placeholder="Min. 6 characters">
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" id="confirm_password" required placeholder="Repeat password">
                </div>

                <button type="submit" class="btn btn-primary btn-block" id="regBtn">Register Now</button>
            </form>
            <p style="margin-top: 1rem; font-size: 0.85rem;">
                Already have an account? <a href="login.php" style="color: var(--primary-color);">Login here</a>
            </p>
        </div>
    </div>

    <script>
    document.getElementById('registerForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const msg = document.getElementById('regMessage');
        const btn = document.getElementById('regBtn');
        
        const password = document.getElementById('password').value;
        const confirm = document.getElementById('confirm_password').value;

        if (password !== confirm) {
            msg.className = 'alert alert-danger';
            msg.textContent = "Passwords do not match!";
            msg.style.display = 'block';
            return;
        }

        btn.disabled = true;
        btn.textContent = 'Creating account...';

        const formData = {
            name: document.getElementById('name').value,
            email: document.getElementById('email').value,
            password: password
        };

        try {
            const response = await fetch('../../api/auth/register.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
            const result = await response.json();

            if (result.success) {