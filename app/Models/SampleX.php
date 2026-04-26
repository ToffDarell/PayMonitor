<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SampleX extends Model
{
    use HasFactory;

    protected $table = 'sample_x';

    protected $fillable = [
        'number',
        'description',
    ];
}
