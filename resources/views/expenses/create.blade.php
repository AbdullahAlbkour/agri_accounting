@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card stat-card p-4">
            <h5 class="fw-bold mb-3 text-danger"><i class="fa-solid fa-plus-circle me-2"></i>تسجيل مصروف جديد</h5>
            
            <form action="{{ route('expenses.store') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">الموسم الزراعي</label>
                        <select name="season_id" class="form-select @error('season_id') is-invalid @enderror" required>
                            <option value="">-- اختر الموسم --</option>
                            @foreach($seasons as $season)
                                <option value="{{ $season->id }}" {{ old('season_id') == $season->id ? 'selected' : '' }}>
                                    {{ $season->name }} ({{ $season->crop->name ?? '' }})
                                </option>
                            @endforeach
                        </select>
                        @error('season_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">نوع / بند المصروف (كتابة حرة)</label>
                        <input type="text" name="category_name" list="category_suggestions" class="form-control @error('category_name') is-invalid @enderror" value="{{ old('category_name') }}" placeholder="مثال: بذار، مازوت، سماد، حراثة، أجور عمال..." required>
                        <datalist id="category_suggestions">
                            @foreach($categories as $cat)
                                <option value="{{ $cat->name }}">
                            @endforeach
                        </datalist>
                        @error('category_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-danger">المبلغ المدفوع</label>
                        <input type="text" dir="ltr" inputmode="decimal" name="amount" class="form-control text-start font-monospace fw-bold @error('amount') is-invalid @enderror" value="{{ old('amount') }}" placeholder="0.00" required>
                        @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">العملة</label>
                        <select name="currency" class="form-select" required>
                            <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>دولار (USD)</option>
                            <option value="TRY" {{ old('currency') == 'TRY' ? 'selected' : '' }}>ليرة تركية (TRY)</option>
                            <option value="SYP" {{ old('currency') == 'SYP' ? 'selected' : '' }}>ليرة سورية (SYP)</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">سعر الصرف مقابل الدولار</label>
                        <input type="text" dir="ltr" inputmode="decimal" name="exchange_rate" class="form-control text-start font-monospace" value="{{ old('exchange_rate') }}" placeholder="1">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">تاريخ المصروف</label>
                        <input type="date" name="date" class="form-control" value="{{ old('date', date('Y-m-d')) }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">تفاصيل / ملاحظات إضافية</label>
                        <input type="text" name="notes" class="form-control" value="{{ old('notes') }}" placeholder="مثال: شراء كيسين سماد يوريا، سقاية أرض السهل...">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('expenses.index') }}" class="btn btn-light px-4">إلغاء</a>
                    <button type="submit" class="btn btn-danger px-4 fw-bold">حفظ المصروف</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('input[dir="ltr"]').forEach(function(input) {
    input.addEventListener('input', function() {
        let arabicNumbers = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
        let value = this.value;
        for (let i = 0; i < 10; i++) {
            value = value.replaceAll(arabicNumbers[i], i);
        }
        this.value = value;
    });
});
</script>
@endsection