<?php
// views/auth/login.php
session_start();

// ==========================================================
// THE BOUNCER: Kapag naka-login na, bawal na dito!
// I-re-redirect natin sila pabalik sa kani-kanilang dashboard.
// ==========================================================
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
    if ($role === 'admin') {
        header("Location: ../admin/dashboard.php");
        exit;
    } elseif ($role === 'registrar') {
        header("Location: ../registrar/dashboard.php");
        exit;
    } elseif ($role === 'faculty') {
        header("Location: ../faculty/dashboard.php");
        exit;
    } elseif ($role === 'student') {
        header("Location: ../student/dashboard.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CHCC Finance - Secure Access Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        /* =========================================
           PREMIUM GLASSMORPHISM CSS v2.1 ✨
           (Compact Version)
           ========================================= */
        :root {
            --primary: #3b82f6; 
            --primary-hover: #2563eb;
            --accent: #a78bfa; 
            --bg-dark: #0f172a; 
            --text-main: #f1f5f9; 
            --text-muted: #94a3b8; 
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        body, html {
            margin: 0; padding: 0; font-family: 'Inter', sans-serif; height: 100vh; width: 100%;
            background-color: var(--bg-dark); overflow: hidden; display: flex;
            justify-content: center; align-items: center; position: relative;
            color: var(--text-main);
        }

        /* Abstract Floating Background Shapes */
        .shape { position: absolute; filter: blur(80px); z-index: 1; opacity: 0.5; animation: float 12s ease-in-out infinite; }
        .shape-1 { width: 500px; height: 500px; background: #1d4ed8; top: -150px; left: -150px; border-radius: 50%; }
        .shape-2 { width: 400px; height: 400px; background: #7c3aed; bottom: -100px; right: -100px; border-radius: 50%; animation-delay: -6s; }
        .shape-3 { width: 300px; height: 300px; background: #059669; bottom: 25%; left: 15%; border-radius: 50%; animation-duration: 15s; }

        @keyframes float {
            0% { transform: translateY(0px) scale(1) rotate(0deg); }
            50% { transform: translateY(-40px) scale(1.08) rotate(5deg); }
            100% { transform: translateY(0px) scale(1) rotate(0deg); }
        }

        /* Subtle background texture overlay */
        body::before {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background-image: url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%239C92AC" fill-opacity="0.05"%3E%3Cpath d="M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4v-4H4v4H0v2h4v4h2v-4h4v-2H6zM36 4v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4z"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');
            z-index: 2; pointer-events: none;
        }

        /* Glassmorphism Login Card - COMPACT SETTINGS */
        .login-card {
            background: rgba(23, 31, 51, 0.8); 
            backdrop-filter: blur(25px) saturate(180%);
            -webkit-backdrop-filter: blur(25px) saturate(180%);
            border: 1px solid var(--glass-border);
            /* PINALIIT NA PADDING: 2rem top/bottom, 2.5rem left/right */
            padding: 2rem 2.5rem; 
            border-radius: 20px; /* Slightly smaller radius */
            width: 100%; 
            max-width: 400px; /* Slightly slimmer */
            box-shadow: 0 35px 60px -15px rgba(0, 0, 0, 0.6), inset 0 0 1px rgba(255,255,255,0.2);
            z-index: 10;
            position: relative; transform: translateY(30px); opacity: 0;
            animation: slideUp 0.8s cubic-bezier(0.22, 1, 0.36, 1) forwards;
        }

        @keyframes slideUp { to { transform: translateY(0); opacity: 1; } }

        /* EXIT ANIMATION MAGIC */
        .page-exit {
            animation: pageExit 0.7s cubic-bezier(0.8, 0, 0.2, 1) forwards;
            pointer-events: none;
        }
        @keyframes pageExit {
            0% { opacity: 1; transform: scale(1); filter: blur(0); }
            100% { opacity: 0; transform: scale(1.08); filter: blur(15px); }
        }

        /* Logo & Brand Styling - COMPACT MARGINS */
        .brand-container { text-align: center; margin-bottom: 2rem; /* Reduced from 3rem */ }
        .logo-icon {
            width: 60px; height: 60px; /* Slightly smaller from 70px */
            background: linear-gradient(135deg, #60a5fa 0%, #a78bfa 100%);
            border-radius: 18px; display: inline-flex; justify-content: center; align-items: center;
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
            margin-bottom: 1rem; /* Reduced from 1.25rem */
            transform: rotate(-8deg);
            border: 2px solid rgba(255,255,255,0.2);
        }
        .logo-text { color: white; font-weight: 900; font-size: 2rem; transform: rotate(8deg); }

        .brand-name { margin: 0; font-size: 1.7rem; letter-spacing: -1px; font-weight: 900; color: white; }
        .brand-name span { color: var(--primary); }
        .brand-subname { margin: 0.4rem 0 0; color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 2px; font-weight: 700; }

        /* Form Styling - COMPACT SPACING */
        .input-group { margin-bottom: 1.25rem; /* Reduced from 1.75rem */ position: relative; }
        .input-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.8rem; color: var(--accent); }
        
        .input-wrapper { position: relative; }
        .input-group input {
            width: 100%; padding: 0.85rem 1rem 0.85rem 3rem; /* Reduced vertical padding from 1rem */
            border: 1px solid var(--glass-border);
            border-radius: 10px; font-size: 0.95rem; font-family: inherit; box-sizing: border-box;
            background: rgba(0, 0, 0, 0.2); color: white;
            transition: all 0.3s ease;
        }
        .input-group input:focus {
            outline: none; border-color: var(--primary);
            background: rgba(0, 0, 0, 0.4);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        .input-icon {
            position: absolute; left: 1rem; top: 50%; transform: translateY(-50%);
            color: #64748b; font-size: 1rem; transition: color 0.3s ease;
        }
        .input-group input:focus + .input-icon { color: var(--primary); }

        /* Login Button - COMPACT */
        .btn-login {
            width: 100%; padding: 0.85rem; /* Reduced vertical padding from 1rem */
            background: linear-gradient(135deg, var(--primary) 0%, #818cf8 100%);
            color: white; border: none; border-radius: 10px; font-size: 1rem;
            font-weight: 800; cursor: pointer; transition: all 0.4s ease;
            box-shadow: 0 6px 12px -3px rgba(59, 130, 246, 0.3);
            margin-top: 0.5rem; /* Reduced from 1.25rem */
            position: relative; overflow: hidden;
        }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 12px 20px -5px rgba(59, 130, 246, 0.4); }
        .btn-login:active { transform: translateY(-1px); }

        /* Error Message Styling - COMPACT */
        #error-msg {
            display: none;
            background: rgba(239, 68, 68, 0.15); 
            color: #fca5a5;
            padding: 0.75rem 1rem; border-radius: 8px; font-size: 0.8rem;
            margin-bottom: 1.25rem; text-align: center;
            border: 1px solid rgba(239, 68, 68, 0.3);
            font-weight: 500;
        }

        /* Footer - COMPACT */
        .login-footer {
            text-align: center; margin-top: 1.75rem; /* Reduced from 2.5rem */
            font-size: 0.75rem; color: var(--text-muted);
            line-height: 1.4;
            border-top: 1px solid var(--glass-border);
            padding-top: 1.25rem; /* Reduced from 1.5rem */
        }
    </style>
</head>
<body>

    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>
    <div class="shape shape-3"></div>

    <div class="login-card">
        
        <div class="brand-container">
            <div class="logo-icon">
                <span class="logo-text">CH</span>
            </div>
            <h1 class="brand-name">
                CHCC <span>FINANCE</span>
            </h1>
            <p class="brand-subname">
                System Portal
            </p>
        </div>

        <div id="error-msg"></div>

        <form id="loginForm">
            <div class="input-group">
                <label for="username">Email or Student No.</label>
                <div class="input-wrapper">
                    <input type="text" id="username" name="username" placeholder="Enter registered email or ID" required autocomplete="username" autofocus>
                    <svg class="input-icon" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </div>
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
                    <svg class="input-icon" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                </div>
            </div>

            <button type="submit" class="btn-login" id="loginBtn">Secure Login</button>
        </form>

        <div class="login-footer">
            &copy; <?php echo date('Y'); ?> CHCC Finance System.<br>
            Authorized Personnel Only.
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const btn = document.getElementById('loginBtn');
            const errorBox = document.getElementById('error-msg');
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;

            btn.innerHTML = 'Authenticating...';
            btn.style.opacity = '0.7';
            btn.style.pointerEvents = 'none';
            errorBox.style.display = 'none';

            try {
                // TANDAAN: Ito ang API endpoint mo base sa structure mo
                const response = await fetch('../../api/auth/login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username, password })
                });

                if (!response.ok) {
                    throw new Error('Server returned ' + response.status);
                }

                const result = await response.json();

                if (result.success) {
                    btn.innerHTML = 'Success! Accessing...';
                    btn.style.background = 'linear-gradient(135deg, #10b981 0%, #34d399 100%)';
                    btn.style.boxShadow = '0 10px 20px rgba(16, 185, 129, 0.4)';
                    
                    setTimeout(() => {
                        document.body.classList.add('page-exit');
                        
                        setTimeout(() => {
                            if (result.role === 'admin') window.location.href = '../admin/dashboard.php';
                            else if (result.role === 'registrar') window.location.href = '../registrar/dashboard.php';
                            else if (result.role === 'faculty') window.location.href = '../faculty/dashboard.php';
                            else if (result.role === 'student') window.location.href = '../student/dashboard.php';
                            else window.location.reload();
                        }, 500); 

                    }, 600); 
                } else {
                    errorBox.innerHTML = '⚠️ ' + (result.message || 'Invalid credentials.');
                    errorBox.style.display = 'block';
                    
                    btn.innerHTML = 'Secure Login';
                    btn.style.opacity = '1';
                    btn.style.pointerEvents = 'auto';
                    
                    errorBox.animate([
                        { transform: 'translateX(0px)' },
                        { transform: 'translateX(-5px)' },
                        { transform: 'translateX(5px)' },
                        { transform: 'translateX(0px)' }
                    ], { duration: 300, iterations: 1 });
                }
            } catch (error) {
                errorBox.innerHTML = '⚠️ System error. Cannot connect to secure server.';
                errorBox.style.display = 'block';
                btn.innerHTML = 'Secure Login';
                btn.style.opacity = '1';
                btn.style.pointerEvents = 'auto';
                console.error('Login error:', error);
            }
        });
    </script>
</body>
</html>