<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait HandlesAttachments
{
    /**
     * قاعدة التحقق من ملف المرفق (صورة أو PDF).
     */
    protected function attachmentRule(): string
    {
        return 'nullable|file|mimes:'.implode(',', config('agri.attachments.mimes'))
            .'|max:'.config('agri.attachments.max_size_kb');
    }

    /**
     * @return array<string, string>
     */
    protected function attachmentMessages(string $field = 'receipt_image'): array
    {
        $maxMb = round(config('agri.attachments.max_size_kb') / 1024, 1);

        return [
            $field.'.mimes' => 'صيغة الملف غير مدعومة. المسموح: صور (JPG, PNG, WEBP, GIF) أو ملف PDF.',
            $field.'.max' => 'حجم الملف كبير جداً. الحد الأقصى '.$maxMb.' ميغابايت.',
        ];
    }

    /**
     * رفع المرفق وإرجاع مساره، أو null إن لم يُرفع ملف.
     *
     * @param  string  $folderKey  مفتاح المجلد ضمن config('agri.attachments.paths')
     */
    protected function storeAttachment(Request $request, string $folderKey, string $field = 'receipt_image'): ?string
    {
        if (! $request->hasFile($field)) {
            return null;
        }

        $folder = config('agri.attachments.paths.'.$folderKey, 'receipts');

        return $request->file($field)->store($folder, config('agri.attachments.disk'));
    }
}
