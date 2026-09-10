<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    use BelongsToUser, HasFactory;

    protected $guarded = [];

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}
