<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول | نظام المحاسبة الزراعية</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Cairo', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f172a 0%, #1b5e20 100%);
        }

        .login-card {
            width: 100%;
            max-width: 430px;
            border: none;
            border-radius: 20px;
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.28);
        }

        .login-brand {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: #e8f5e9;
            color: #1b5e20;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 12px;
        }
    </style>
</head>
<body>

<div class="card login-card p-4">
    <div class="text-center mb-4">
        <div class="login-brand"><i class="fa-solid fa-leaf"></i></div>
        <h4 class="fw-bold mb-1">نظام المحاسبة الزراعية</h4>
        <p class="text-muted mb-0">سجّل الدخول للوصول إلى حساباتك</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger py-2">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger py-2">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('login.attempt') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label class="form-label fw-semibold">اسم المستخدم أو البريد الإلكتروني</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="fa-solid fa-user"></i></span>
                <input type="text" name="login" class="form-control" value="{{ old('login') }}" placeholder="username أو admin@agri.local" required autofocus>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">كلمة المرور</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="fa-solid fa-lock"></i></span>
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                    <i class="fa-solid fa-eye" id="eyeIcon"></i>
                </button>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="form-check mb-0">
                <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                <label class="form-check-label" for="remember">تذكّرني على هذا الجهاز</label>
            </div>
            <a href="{{ route('password.request') }}" class="small text-decoration-none">نسيت كلمة المرور؟</a>
        </div>

        <button type="submit" class="btn btn-success w-100 py-2 fw-bold rounded-pill">
            <i class="fa-solid fa-right-to-bracket me-1"></i> تسجيل الدخول
        </button>
    </form>

    <hr class="my-4">

    <p class="text-center text-muted mb-0">
        ليس لديك حساب؟
        <a href="{{ route('register') }}" class="fw-bold text-decoration-none">أنشئ حساب مزارع جديد</a>
    </p>
</div>

<script>
    function togglePassword() {
        const input = document.getElementById('password');
        const icon = document.getElementById('eyeIcon');
        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        icon.classList.toggle('fa-eye', !isHidden);
        icon.classList.toggle('fa-eye-slash', isHidden);
    }
</script>

</body>
</html>
