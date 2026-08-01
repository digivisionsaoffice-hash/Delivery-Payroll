<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول — وليم</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Cairo', sans-serif;
            background: #0d1117;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* Background animated gradient */
        body::before {
            content: '';
            position: fixed;
            width: 600px; height: 600px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(37,99,235,0.15) 0%, transparent 70%);
            top: -100px; right: -100px;
            animation: pulse 4s ease-in-out infinite;
        }
        body::after {
            content: '';
            position: fixed;
            width: 400px; height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(16,185,129,0.1) 0%, transparent 70%);
            bottom: -50px; left: -50px;
            animation: pulse 4s ease-in-out infinite 2s;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 1rem;
            position: relative;
            z-index: 1;
            animation: fadeInUp 0.5s ease;
        }

        @keyframes fadeInUp {
            from { opacity:0; transform: translateY(20px); }
            to   { opacity:1; transform: translateY(0); }
        }

        .login-card {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }

        .logo-area {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-icon {
            width: 64px; height: 64px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1rem;
            box-shadow: 0 8px 20px rgba(102,126,234,0.3);
        }

        .logo-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: #e6edf3;
        }

        .logo-sub {
            font-size: 0.8rem;
            color: #8b949e;
            margin-top: 0.25rem;
        }

        .form-group { margin-bottom: 1.25rem; }

        .form-label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: #8b949e;
            margin-bottom: 0.5rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            right: 0.9rem; top: 50%;
            transform: translateY(-50%);
            color: #8b949e;
            font-size: 1rem;
            pointer-events: none;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 2.5rem 0.75rem 1rem;
            background: #21262d;
            border: 1px solid #30363d;
            border-radius: 10px;
            color: #e6edf3;
            font-family: 'Cairo', sans-serif;
            font-size: 0.9rem;
            transition: all 0.2s;
            outline: none;
        }

        .form-input:focus {
            border-color: #2563eb;
            background: #161b22;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.2);
        }

        .form-input::placeholder { color: #484f58; }

        .form-checkbox-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .form-checkbox {
            width: 16px; height: 16px;
            accent-color: #2563eb;
        }

        .form-checkbox-label {
            font-size: 0.8rem;
            color: #8b949e;
        }

        .btn-login {
            width: 100%;
            padding: 0.85rem;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border: none;
            border-radius: 10px;
            color: white;
            font-family: 'Cairo', sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
            overflow: hidden;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.4s;
        }

        .btn-login:hover::before { left: 100%; }
        .btn-login:hover { box-shadow: 0 6px 20px rgba(37,99,235,0.4); transform: translateY(-1px); }
        .btn-login:active { transform: translateY(0); }

        .error-msg {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.3);
            border-radius: 8px;
            padding: 0.6rem 0.9rem;
            color: #ef4444;
            font-size: 0.8rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .footer-text {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.75rem;
            color: #484f58;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-card">
        <div class="logo-area">
            <div class="logo-icon">🚀</div>
            <div class="logo-title">وليم</div>
            <div class="logo-sub">نظام إدارة الرواتب والربحية</div>
        </div>

        @if(session('status'))
        <div class="error-msg" style="background:rgba(16,185,129,0.1); border-color:rgba(16,185,129,0.3); color:#10b981;">
            <i class="bi bi-check-circle"></i> {{ session('status') }}
        </div>
        @endif

        @if($errors->any())
        <div class="error-msg">
            <i class="bi bi-exclamation-circle"></i>
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="form-group">
                <label class="form-label">البريد الإلكتروني</label>
                <div class="input-wrapper">
                    <i class="bi bi-envelope input-icon"></i>
                    <input type="email" name="email" class="form-input"
                           placeholder="admin@walim.test"
                           value="{{ old('email') }}" required autofocus>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">كلمة المرور</label>
                <div class="input-wrapper">
                    <i class="bi bi-lock input-icon"></i>
                    <input type="password" name="password" class="form-input"
                           placeholder="••••••••" required>
                </div>
            </div>

            <div class="form-checkbox-row">
                <input type="checkbox" class="form-checkbox" name="remember" id="remember">
                <label for="remember" class="form-checkbox-label">تذكرني</label>
            </div>

            <button type="submit" class="btn-login">
                <i class="bi bi-box-arrow-in-right"></i>
                دخول إلى النظام
            </button>
        </form>

        <div class="footer-text">
            © {{ date('Y') }} وليم — نظام إدارة شركات التوصيل
        </div>
    </div>
</div>

</body>
</html>
