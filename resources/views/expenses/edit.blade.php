@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card stat-card p-4">
            <h5 class="fw-bold mb-3 text-primary"><i class="fa-solid fa-pen-to-square me-2"></i>تعديل بيانات المصروف</h5>
            
            <form action="{{ route('expenses.update', $expense) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">الموسم الزراعي</label>
                        <select name="season_id" class="form-select @error('season_id') is-invalid @enderror" required>
                            @foreach($seasons as $season)
                                <option value="{{ $season->id }}" {{ old('season_id', $expense->season_id) == $season->id ? 'selected' : '' }}>
                                    {{ $season->name }} ({{ $season->crop->name ?? '' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">نوع / بند المصروف</label>
                        <input type="text" name="category_name" list="category_suggestions" class="form-control @error('category_name') is-invalid @enderror" value="{{ old('category_name', $expense->category->name ?? '') }}" placeholder="أدخل اسم البند" required>
                        <datalist id="category_suggestions">
                            @foreach($categories as $cat)
                                <option value="{{ $cat->name }}">
                            @endforeach
                        </datalist>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-danger">المبلغ المدفوع</label>
                        <input type="text" dir="ltr" inputmode="decimal" name="amount" class="form-control text-start font-monospace fw-bold @error('amount') is-invalid @enderror" value="{{ old('amount', $expense->amount) }}" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">العملة</label>
                        <select name="currency" class="form-select" required>
                            <option value="USD" {{ old('currency', $expense->currency) == 'USD' ? 'selected' : '' }}>دولار (USD)</option>
                            <option value="TRY" {{ old('currency', $expense->currency) == 'TRY' ? 'selected' : '' }}>ليرة تركية (TRY)</option>
                            <option value="SYP" {{ old('currency', $expense->currency) == 'SYP' ? 'selected' : '' }}>ليرة سورية (SYP)</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">سعر الصرف مقابل الدولار</label>
                        <input type="text" dir="ltr" inputmode="decimal" name="exchange_rate" class="form-control text-start font-monospace" value="{{ old('exchange_rate', $expense->exchange_rate) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">تاريخ المصروف</label>
                        <input type="date" name="date" class="form-control" value="{{ old('date', $expense->date) }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">تفاصيل / ملاحظات إضافية</label>
                        <input type="text" name="notes" class="form-control" value="{{ old('notes', $expense->notes) }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">
                            <i class="fa-solid fa-paperclip me-1"></i> صورة فاتورة الشراء / إيصال الصرف
                        </label>

                        @if($expense->hasReceipt())
                        <div class="d-flex align-items-center gap-3 p-3 mb-2 bg-light border rounded">
                            @include('partials.receipt-thumb', ['model' => $expense, 'title' => 'فاتورة المصروف'])
                            <div class="small">
                                <div class="fw-semibold">المرفق الحالي</div>
                                <a href="{{ $expense->receiptUrl() }}" target="_blank" class="text-decoration-none">فتح / تحميل</a>
                            </div>
                            <div class="form-check ms-auto">
                                <input class="form-check-input" type="checkbox" name="remove_receipt" value="1" id="remove_receipt">
                                <label class="form-check-label text-danger" for="remove_receipt">حذف المرفق الحالي</label>
                            </div>
                        </div>
                        @endif

                        <input type="file" name="receipt_image" accept="image/*,application/pdf"
                               class="form-control @error('receipt_image') is-invalid @enderror">
                        <small class="text-muted">
                            اختيار ملف جديد يستبدل المرفق الحالي. الصيغ المدعومة: JPG, PNG, WEBP, GIF, PDF —
                            بحد أقصى {{ round(config('agri.attachments.max_size_kb') / 1024, 1) }} ميغابايت.
                        </small>
                        @error('receipt_image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('expenses.index') }}" class="btn btn-light px-4">إلغاء</a>
                    <button type="submit" class="btn btn-primary px-4 fw-bold">حفظ التعديلات</button>
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