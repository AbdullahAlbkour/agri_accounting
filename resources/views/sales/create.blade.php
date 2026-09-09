@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-9">
        <div class="card stat-card p-4">
            <h5 class="fw-bold mb-3 text-success"><i class="fa-solid fa-plus-circle me-2"></i>تسجيل مبيعات جديدة</h5>
            
            <form action="{{ route('sales.store') }}" method="POST">
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
                        <label class="form-label fw-semibold">اسم المشتري / التاجر</label>
                        <input type="text" name="buyer_name" class="form-control @error('buyer_name') is-invalid @enderror" value="{{ old('buyer_name') }}" placeholder="أدخل اسم التاجر أو المشتري" required>
                        @error('buyer_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- الوزن: طن + كيلو بدون قيم مسبقة -->
                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-primary">الوزن (طن)</label>
                        <input type="number" step="any" min="0" name="tons" id="tons" class="form-control" value="{{ old('tons') }}" placeholder="0" oninput="calcTotal()">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-primary">الوزن الزائد (كغ)</label>
                        <input type="number" step="any" min="0" max="999.9" name="extra_kg" id="extra_kg" class="form-control" value="{{ old('extra_kg') }}" placeholder="0" oninput="calcTotal()">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">سعر الطن الواحد</label>
                        <input type="number" step="any" name="unit_price" id="unit_price" class="form-control @error('unit_price') is-invalid @enderror" value="{{ old('unit_price') }}" placeholder="مثال: 250" required oninput="calcTotal()">
                        @error('unit_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-success">المبلغ الإجمالي المحسوب</label>
                        <input type="text" id="calculated_display" class="form-control fw-bold bg-light text-success font-monospace" readonly placeholder="0.00">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">المبلغ المقبوض (الواصل)</label>
                        <input type="number" step="any" min="0" name="paid_amount" class="form-control @error('paid_amount') is-invalid @enderror" value="{{ old('paid_amount') }}" placeholder="اتركه فارغاً إذا كان آجل بالكامل">
                        @error('paid_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
                        <input type="number" step="any" name="exchange_rate" class="form-control" value="{{ old('exchange_rate') }}" placeholder="1">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">تاريخ البيع</label>
                        <input type="date" name="date" class="form-control" value="{{ old('date', date('Y-m-d')) }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">ملاحظات</label>
                        <input type="text" name="notes" class="form-control" value="{{ old('notes') }}" placeholder="مكان القبان، رقم السيارة، ملاحظات أخرى...">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('sales.index') }}" class="btn btn-light px-4">إلغاء</a>
                    <button type="submit" class="btn btn-success px-4 fw-bold">حفظ عملية البيع</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function calcTotal() {
    let tons = parseFloat(document.getElementById('tons').value) || 0;
    let kg = parseFloat(document.getElementById('extra_kg').value) || 0;
    let pricePerTon = parseFloat(document.getElementById('unit_price').value) || 0;

    let totalWeightInTons = tons + (kg / 1000);
    let total = totalWeightInTons * pricePerTon;

    document.getElementById('calculated_display').value = total > 0 ? total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '';
}
</script>
@endsection