<?php

namespace App\Http\Controllers\Concerns;

trait NormalizesNumbers
{
    /**
     * تحويل الأرقام العربية/الهندية إلى أرقام إنجليزية وإزالة فواصل الآلاف.
     */
    protected function normalizeNumber($value): string
    {
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩', '،', ','];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '', ''];

        return trim(str_replace($arabic, $english, (string) $value));
    }
}
