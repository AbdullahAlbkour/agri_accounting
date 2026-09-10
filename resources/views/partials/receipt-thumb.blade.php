{{-- مصغّر المرفق: صورة قابلة للتكبير أو أيقونة PDF --}}
@php($title = $title ?? 'المرفق')

@if($model->hasReceipt())
    @if($model->receiptIsPdf())
        <a href="{{ $model->receiptUrl() }}" target="_blank" class="btn btn-sm btn-outline-danger" title="فتح ملف PDF">
            <i class="fa-solid fa-file-pdf"></i>
        </a>
    @else
        <img src="{{ $model->receiptUrl() }}"
             alt="{{ $title }}"
             role="button"
             data-attachment-url="{{ $model->receiptUrl() }}"
             data-attachment-title="{{ $title }}"
             class="rounded border"
             style="width: 44px; height: 44px; object-fit: cover; cursor: zoom-in;">
    @endif
@else
    <span class="text-muted small">—</span>
@endif
