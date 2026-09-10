<?php

namespace App\Models\Concerns;

use App\Models\Scopes\OwnedByUserScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * يربط النموذج بالمستخدم المالك (المزارع) ويطبّق عزل البيانات تلقائياً.
 */
trait BelongsToUser
{
    public static function bootBelongsToUser(): void
    {
        static::addGlobalScope(new OwnedByUserScope);

        static::creating(function (Model $model) {
            if (empty($model->user_id) && Auth::check()) {
                $model->user_id = Auth::id();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * تجاهل عزل البيانات (للاستخدام في لوحة المدير والتقارير الإدارية).
     */
    public function scopeWithoutOwnershipScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope(OwnedByUserScope::class);
    }

    /**
     * بيانات مستخدم بعينه بغض النظر عن المستخدم المسجّل حالياً.
     */
    public function scopeOwnedBy(Builder $query, $userId): Builder
    {
        return $query->withoutGlobalScope(OwnedByUserScope::class)->where('user_id', $userId);
    }
}
