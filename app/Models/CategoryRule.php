<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryRule extends Model
{
    protected $fillable = [
        'category',
        'pattern',
    ];
}
