<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * نطاق عزل البيانات: كل مزارع يرى بياناته فقط، بينما يرى مدير النظام كل البيانات.
 *
 * لا يُطبّق أي فلترة عندما لا يوجد مستخدم مسجّل (سطر الأوامر، المهاجرات، الاختبارات).
 */
class OwnedByUserScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if (! $user || $user->isAdmin()) {
            return;
        }

        $builder->where($model->getTable().'.user_id', $user->getAuthIdentifier());
    }
}
