// public/js/auth.js

document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');
    const loginError = document.getElementById('loginError');
    const loginBtn = document.getElementById('loginBtn');

    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault(); // Prevent standard form submission

            // Hide previous errors & disable button
            loginError.style.display = 'none';
            loginBtn.disabled = true;
            loginBtn.textContent = 'Signing in...';

            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            try {
                // Call the PHP API
                const response = await fetch('../../api/auth/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ email, password })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    // Redirect based on user role
                    const role = data.user.role;
                    window.location.href = `../../views/${role}/dashboard.php`;
                } else {
                    // Show error message from API
                    loginError.textContent = data.message || 'Invalid login credentials.';
                    loginError.style.display = 'block';
                    loginBtn.disabled = false;
                    loginBtn.textContent = 'Sign In';
                }

            } catch (error) {
                console.error('Login Error:', error);
                loginError.textContent = 'An error occurred. Please try again.';
                loginError.style.display = 'block';
                loginBtn.disabled = false;
                loginBtn.textContent = 'Sign In';
            }
        });
    }
});