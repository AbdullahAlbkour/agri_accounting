<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * إدارة المرفق (صورة الفاتورة أو السند) المرتبط بالسجل.
 */
trait HasReceiptAttachment
{
    public static function bootHasReceiptAttachment(): void
    {
        // حذف الملف من التخزين عند حذف السجل
        static::deleting(function (Model $model) {
            $model->deleteReceiptFile();
        });
    }

    public function hasReceipt(): bool
    {
        return ! empty($this->receipt_image);
    }

    /**
     * رابط عرض المرفق، أو null إن لم يوجد.
     */
    public function receiptUrl(): ?string
    {
        if (! $this->hasReceipt()) {
            return null;
        }

        return Storage::disk(config('agri.attachments.disk'))->url($this->receipt_image);
    }

    /**
     * هل المرفق ملف PDF (وليس صورة)؟
     */
    public function receiptIsPdf(): bool
    {
        return $this->hasReceipt()
            && strtolower(pathinfo($this->receipt_image, PATHINFO_EXTENSION)) === 'pdf';
    }

    public function receiptFileName(): ?string
    {
        return $this->hasReceipt() ? basename($this->receipt_image) : null;
    }

    /**
     * حذف ملف المرفق من التخزين (دون حفظ السجل).
     */
    public function deleteReceiptFile(): void
    {
        if (! $this->hasReceipt()) {
            return;
        }

        Storage::disk(config('agri.attachments.disk'))->delete($this->receipt_image);
    }
}
