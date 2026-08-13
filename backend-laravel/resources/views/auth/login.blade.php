<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MACSON</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: #070d1a;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* Animated background grid */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(56, 189, 248, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(56, 189, 248, 0.04) 1px, transparent 1px);
            background-size: 40px 40px;
            animation: gridMove 20s linear infinite;
            pointer-events: none;
            z-index: 0;
        }

        @keyframes gridMove {
            0%   { transform: translate(0, 0); }
            100% { transform: translate(40px, 40px); }
        }

        /* Glow orbs */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(120px);
            opacity: 0.12;
            pointer-events: none;
            z-index: 0;
            animation: float 8s ease-in-out infinite;
        }
        .orb-1 { width: 500px; height: 500px; background: #38bdf8; top: -150px; left: -150px; animation-delay: 0s; }
        .orb-2 { width: 400px; height: 400px; background: #818cf8; bottom: -100px; right: -100px; animation-delay: -4s; }
        .orb-3 { width: 300px; height: 300px; background: #34d399; top: 50%; left: 50%; animation-delay: -2s; }

        @keyframes float {
            0%, 100% { transform: translateY(0) scale(1); }
            50%       { transform: translateY(-30px) scale(1.05); }
        }

        .login-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 440px;
            padding: 16px;
        }

        .login-card {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(56, 189, 248, 0.15);
            border-radius: 20px;
            padding: 48px 40px;
            box-shadow:
                0 0 0 1px rgba(56, 189, 248, 0.05),
                0 32px 64px rgba(0, 0, 0, 0.6),
                0 0 80px rgba(56, 189, 248, 0.05);
            animation: cardIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes cardIn {
            from { opacity: 0; transform: translateY(24px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        .brand-logo {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #0ea5e9, #6366f1);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 28px;
            color: white;
            box-shadow: 0 8px 32px rgba(14, 165, 233, 0.35);
            animation: logoGlow 3s ease-in-out infinite;
        }

        @keyframes logoGlow {
            0%, 100% { box-shadow: 0 8px 32px rgba(14, 165, 233, 0.35); }
            50%       { box-shadow: 0 8px 48px rgba(14, 165, 233, 0.6); }
        }

        .login-title {
            font-size: 26px;
            font-weight: 800;
            color: #f8fafc;
            text-align: center;
            margin-bottom: 4px;
            letter-spacing: -0.5px;
        }

        .login-subtitle {
            color: #64748b;
            text-align: center;
            font-size: 13.5px;
            margin-bottom: 32px;
        }

        .login-subtitle span {
            color: #38bdf8;
            font-weight: 600;
        }

        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: #94a3b8;
            margin-bottom: 8px;
            letter-spacing: 0.3px;
        }

        .input-group-custom {
            position: relative;
            margin-bottom: 18px;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #475569;
            font-size: 15px;
            z-index: 2;
            transition: color 0.2s;
        }

        .form-control-custom {
            width: 100%;
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(71, 85, 105, 0.5);
            border-radius: 12px;
            color: #f1f5f9;
            font-size: 14.5px;
            padding: 13px 16px 13px 42px;
            font-family: 'Inter', sans-serif;
            transition: all 0.25s ease;
            outline: none;
        }

        .form-control-custom::placeholder { color: #475569; }

        .form-control-custom:focus {
            border-color: #38bdf8;
            background: rgba(30, 41, 59, 1);
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.12);
        }

        .form-control-custom:focus + .input-icon,
        .input-group-custom:focus-within .input-icon {
            color: #38bdf8;
        }

        .input-icon { pointer-events: none; }

        /* Password toggle */
        .toggle-pw {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #475569;
            cursor: pointer;
            font-size: 15px;
            z-index: 2;
            transition: color 0.2s;
            padding: 0;
        }
        .toggle-pw:hover { color: #94a3b8; }

        .remember-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 24px;
        }

        .form-check-input-custom {
            width: 18px;
            height: 18px;
            background-color: rgba(30, 41, 59, 0.8);
            border: 1px solid #475569;
            border-radius: 5px;
            cursor: pointer;
            accent-color: #38bdf8;
            flex-shrink: 0;
        }

        .remember-label {
            font-size: 13.5px;
            color: #64748b;
            cursor: pointer;
            user-select: none;
        }

        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, #0ea5e9 0%, #6366f1 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 15px;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            padding: 14px;
            cursor: pointer;
            transition: all 0.25s ease;
            letter-spacing: 0.3px;
            position: relative;
            overflow: hidden;
        }

        .btn-login::after {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(255,255,255,0);
            transition: background 0.2s;
        }

        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(14, 165, 233, 0.4);
        }
        .btn-login:hover::after { background: rgba(255,255,255,0.07); }
        .btn-login:active { transform: translateY(0); }

        .btn-login .spinner {
            display: none;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            margin-right: 8px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        .alert-custom {
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 13.5px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            border: none;
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.25);
            color: #fca5a5;
        }
        .alert-success {
            background: rgba(34, 197, 94, 0.12);
            border: 1px solid rgba(34, 197, 94, 0.25);
            color: #86efac;
        }

        .divider {
            border: none;
            border-top: 1px solid rgba(71, 85, 105, 0.3);
            margin: 28px 0 20px;
        }

        .footer-info {
            text-align: center;
            font-size: 12px;
            color: #334155;
        }
        .footer-info strong { color: #475569; }

        /* Ripple effect */
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255,255,255,0.15);
            transform: scale(0);
            animation: rippleAnim 0.6s linear;
            pointer-events: none;
        }
        @keyframes rippleAnim {
            to { transform: scale(4); opacity: 0; }
        }
    </style>
</head>
<body>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    <div class="login-wrapper">
        <div class="login-card">
            <!-- Brand -->
            <div class="brand-logo">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <h1 class="login-title">Welcome to SANTAFE</h1>
            <p class="login-subtitle">
                <span>Santos Advanced Network Traffic & Access Filtering Engine</span>
            </p>

            <!-- Alerts -->
            @if(session('error'))
                <div class="alert-custom alert-error" role="alert">
                    <i class="fa-solid fa-circle-xmark fa-lg"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif
            @if(session('success'))
                <div class="alert-custom alert-success" role="alert">
                    <i class="fa-solid fa-circle-check fa-lg"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if($errors->has('email') || $errors->has('password'))
                <div class="alert-custom alert-error" role="alert">
                    <i class="fa-solid fa-circle-xmark fa-lg"></i>
                    <span>{{ $errors->first('email') ?: $errors->first('password') }}</span>
                </div>
            @endif

            <!-- Login Form -->
            <form id="loginForm" method="POST" action="{{ route('login') }}" novalidate>
                @csrf

                <!-- Email -->
                <div class="mb-1">
                    <label class="form-label" for="email">Email Address</label>
                </div>
                <div class="input-group-custom">
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control-custom"
                        placeholder="admin@radius.local"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        required
                    >
                    <i class="fa-solid fa-envelope input-icon"></i>
                </div>

                <!-- Password -->
                <div class="mb-1">
                    <label class="form-label" for="password">Password</label>
                </div>
                <div class="input-group-custom" style="margin-bottom: 14px;">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control-custom"
                        placeholder="••••••••••"
                        autocomplete="current-password"
                        required
                    >
                    <i class="fa-solid fa-lock input-icon"></i>
                    <button type="button" class="toggle-pw" id="togglePw" tabindex="-1" title="Toggle password visibility">
                        <i class="fa-solid fa-eye" id="eyeIcon"></i>
                    </button>
                </div>

                <!-- Remember Me -->
                <div class="remember-row">
                    <input type="checkbox" id="remember" name="remember" class="form-check-input-custom">
                    <label for="remember" class="remember-label">Keep me signed in</label>
                </div>

                <!-- Submit -->
                <button type="submit" class="btn-login" id="btnLogin">
                    <span class="spinner" id="loginSpinner"></span>
                    <i class="fa-solid fa-right-to-bracket me-2" id="loginIcon"></i>
                    Sign In to Dashboard
                </button>
            </form>

            <hr class="divider">
            <div class="footer-info">
                <strong>MACSON</strong> v1.0.0 &nbsp;&bull;&nbsp; Enterprise RADIUS Management<br>
                <span style="color:#1e293b;">Unauthorized access is strictly prohibited.</span>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        const togglePw  = document.getElementById('togglePw');
        const pwInput   = document.getElementById('password');
        const eyeIcon   = document.getElementById('eyeIcon');

        togglePw.addEventListener('click', () => {
            const isText = pwInput.type === 'text';
            pwInput.type = isText ? 'password' : 'text';
            eyeIcon.className = isText ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
        });

        // Loading state on submit
        const form      = document.getElementById('loginForm');
        const btnLogin  = document.getElementById('btnLogin');
        const spinner   = document.getElementById('loginSpinner');
        const loginIcon = document.getElementById('loginIcon');

        form.addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const pass  = document.getElementById('password').value;
            if (!email || !pass) return;

            btnLogin.disabled   = true;
            spinner.style.display  = 'inline-block';
            loginIcon.style.display = 'none';
            btnLogin.querySelector('span + i') && (btnLogin.textContent = '');
            btnLogin.innerHTML = '<span class="spinner" style="display:inline-block;width:18px;height:18px;border:2px solid rgba(255,255,255,0.3);border-top-color:white;border-radius:50%;animation:spin 0.7s linear infinite;margin-right:8px;vertical-align:middle;"></span> Signing In...';
        });

        // Ripple effect on login button
        btnLogin.addEventListener('click', function(e) {
            const rect   = this.getBoundingClientRect();
            const ripple = document.createElement('span');
            const size   = Math.max(rect.width, rect.height);
            ripple.className = 'ripple';
            ripple.style.cssText = `width:${size}px;height:${size}px;left:${e.clientX - rect.left - size/2}px;top:${e.clientY - rect.top - size/2}px;`;
            this.appendChild(ripple);
            setTimeout(() => ripple.remove(), 600);
        });
    </script>
</body>
</html>
