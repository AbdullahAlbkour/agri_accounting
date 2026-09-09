@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-9">
        <div class="card stat-card p-4">
            <h5 class="fw-bold mb-3 text-primary"><i class="fa-solid fa-pen-to-square me-2"></i>تعديل فاتورة مبيعات</h5>
            
            <form action="{{ route('sales.update', $sale) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">الموسم الزراعي</label>
                        <select name="season_id" class="form-select" required>
                            @foreach($seasons as $season)
                                <option value="{{ $season->id }}" {{ old('season_id', $sale->season_id) == $season->id ? 'selected' : '' }}>
                                    {{ $season->name }} ({{ $season->crop->name ?? '' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">اسم المشتري / التاجر</label>
                        <input type="text" name="buyer_name" class="form-control" value="{{ old('buyer_name', $sale->buyer_name) }}" required>
                    </div>

                    <!-- الوزن: طن + كغ -->
                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-primary">الوزن (طن)</label>
                        <input type="number" step="1" min="0" name="tons" id="tons" class="form-control" value="{{ old('tons', $tons) }}" oninput="calcTotal()">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-primary">الوزن الزائد (كغ)</label>
                        <input type="number" step="0.5" min="0" max="999.9" name="extra_kg" id="extra_kg" class="form-control" value="{{ old('extra_kg', $extra_kg) }}" oninput="calcTotal()">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">سعر الطن الواحد</label>
                        <input type="number" step="0.01" name="unit_price" id="unit_price" class="form-control" value="{{ old('unit_price', $sale->unit_price) }}" required oninput="calcTotal()">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-success">المبلغ الإجمالي المحسوب</label>
                        <input type="text" id="calculated_display" class="form-control fw-bold bg-light text-success font-monospace" readonly value="{{ number_format($sale->total_price, 2) }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">المبلغ المقبوض (الواصل)</label>
                        <input type="number" step="0.01" name="paid_amount" class="form-control" value="{{ old('paid_amount', $sale->paid_amount) }}" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">العملة</label>
                        <select name="currency" class="form-select" required>
                            <option value="USD" {{ old('currency', $sale->currency) == 'USD' ? 'selected' : '' }}>دولار (USD)</option>
                            <option value="TRY" {{ old('currency', $sale->currency) == 'TRY' ? 'selected' : '' }}>ليرة تركية (TRY)</option>
                            <option value="SYP" {{ old('currency', $sale->currency) == 'SYP' ? 'selected' : '' }}>ليرة سورية (SYP)</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">سعر الصرف مقابل الدولار</label>
                        <input type="number" step="0.0001" name="exchange_rate" class="form-control" value="{{ old('exchange_rate', $sale->exchange_rate) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">تاريخ البيع</label>
                        <input type="date" name="date" class="form-control" value="{{ old('date', $sale->date) }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">ملاحظات</label>
                        <input type="text" name="notes" class="form-control" value="{{ old('notes', $sale->notes) }}">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('sales.index') }}" class="btn btn-light px-4">إلغاء</a>
                    <button type="submit" class="btn btn-primary px-4 fw-bold">حفظ التعديلات</button>
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

    document.getElementById('calculated_display').value = total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
</script>
@endsection