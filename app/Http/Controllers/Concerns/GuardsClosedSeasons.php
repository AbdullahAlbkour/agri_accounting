<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Season;

trait GuardsClosedSeasons
{
    /**
     * إرجاع رسالة الخطأ إذا كان الموسم مغلقاً (مؤرشفاً)، أو null إذا كان التعديل مسموحاً.
     */
    protected function closedSeasonMessage(?Season $season): ?string
    {
        if ($season && $season->isClosed()) {
            return 'الموسم "'.$season->name.'" مغلق ومؤرشف، ولا يمكن إضافة أو تعديل أو حذف حركاته المالية. أعد فتح الموسم أولاً من صفحة المواسم.';
        }

        return null;
    }

    /**
     * التحقق من موسم عبر معرّفه.
     */
    protected function closedSeasonMessageById($seasonId): ?string
    {
        return $this->closedSeasonMessage($seasonId ? Season::find($seasonId) : null);
    }
}
