<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعيين كلمة مرور جديدة | نظام المحاسبة الزراعية</title>

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
            padding: 20px;
            background: linear-gradient(135deg, #0f172a 0%, #1b5e20 100%);
        }
        .auth-card { width: 100%; max-width: 460px; border: none; border-radius: 20px; box-shadow: 0 18px 45px rgba(0,0,0,.28); }
        .auth-brand { width: 72px; height: 72px; border-radius: 50%; background: #e8f5e9; color: #1b5e20; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 12px; }
    </style>
</head>
<body>

<div class="card auth-card p-4">
    <div class="text-center mb-4">
        <div class="auth-brand"><i class="fa-solid fa-lock-open"></i></div>
        <h4 class="fw-bold mb-1">تعيين كلمة مرور جديدة</h4>
        <p class="text-muted mb-0">اختر كلمة مرور قوية لا تقل عن 8 أحرف</p>
    </div>

    @if(session('error'))
        <div class="alert alert-danger py-2">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger py-2">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('password.update') }}" method="POST">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="mb-3">
            <label class="form-label fw-semibold">البريد الإلكتروني</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="fa-solid fa-envelope"></i></span>
                <input type="email" dir="ltr" name="email" class="form-control text-start" value="{{ old('email', $email) }}" required>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">كلمة المرور الجديدة</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="fa-solid fa-lock"></i></span>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">تأكيد كلمة المرور</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="fa-solid fa-lock"></i></span>
                <input type="password" name="password_confirmation" class="form-control" placeholder="••••••••" required>
            </div>
        </div>

        <button type="submit" class="btn btn-success w-100 py-2 fw-bold rounded-pill">
            <i class="fa-solid fa-floppy-disk me-1"></i> حفظ كلمة المرور الجديدة
        </button>
    </form>
</div>

</body>
</html>
