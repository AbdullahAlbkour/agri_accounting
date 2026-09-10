<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء حساب جديد | نظام المحاسبة الزراعية</title>

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
            padding: 30px 15px;
            background: linear-gradient(135deg, #0f172a 0%, #1b5e20 100%);
        }

        .register-card {
            width: 100%;
            max-width: 640px;
            border: none;
            border-radius: 20px;
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.28);
        }

        .register-brand {
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

<div class="card register-card p-4">
    <div class="text-center mb-4">
        <div class="register-brand"><i class="fa-solid fa-user-plus"></i></div>
        <h4 class="fw-bold mb-1">إنشاء حساب مزارع جديد</h4>
        <p class="text-muted mb-0">بياناتك ومواسمك ومحاصيلك تبقى خاصة بك وحدك</p>
    </div>

    @if($errors->any())
        <div class="alert alert-danger py-2">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('register.store') }}" method="POST">
        @csrf

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold">الاسم الكامل</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="fa-solid fa-id-card"></i></span>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="مثال: أحمد العلي" required autofocus>
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">اسم المستخدم (فريد)</label>
                <div class="input-group">
                    <span class="input-group-text bg-light">@</span>
                    <input type="text" dir="ltr" name="username" class="form-control text-start @error('username') is-invalid @enderror" value="{{ old('username') }}" placeholder="ahmad_ali" required>
                </div>
                <small class="text-muted">أحرف إنجليزية وأرقام و ( _ . - ) فقط، 3 أحرف على الأقل.</small>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">البريد الإلكتروني (Gmail)</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="fa-solid fa-envelope"></i></span>
                    <input type="email" dir="ltr" name="email" class="form-control text-start @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="example@gmail.com" required>
                </div>
                <small class="text-muted">يُستخدم لاستعادة كلمة المرور، فاحرص على أن يكون بريداً فعّالاً.</small>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">رقم الهاتف (اختياري)</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="fa-solid fa-phone"></i></span>
                    <input type="text" dir="ltr" name="phone" class="form-control text-start" value="{{ old('phone') }}" placeholder="09xxxxxxxx">
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">كلمة المرور</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="8 أحرف على الأقل" required>
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">تأكيد كلمة المرور</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="password_confirmation" class="form-control" placeholder="أعد كتابة كلمة المرور" required>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-success w-100 py-2 fw-bold rounded-pill mt-4">
            <i class="fa-solid fa-user-plus me-1"></i> إنشاء الحساب
        </button>
    </form>

    <hr class="my-4">

    <p class="text-center text-muted mb-0">
        لديك حساب بالفعل؟
        <a href="{{ route('login') }}" class="fw-bold text-decoration-none">تسجيل الدخول</a>
    </p>
</div>

</body>
</html>
