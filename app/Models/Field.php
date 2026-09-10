<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Field extends Model
{
    use BelongsToUser, HasFactory;

    protected $guarded = [];

    public function seasons()
    {
        return $this->hasMany(Season::class);
    }
}
