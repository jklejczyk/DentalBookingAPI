<?php

namespace App\Models;

use App\Http\Enums\GenderEnum;
use App\Http\Filters\V1\QueryFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;

    protected $table = 'patients';

    protected $fillable = ['first_name', 'last_name', 'birthday', 'pesel', 'gender', 'address', 'email'];

    protected $casts = [
        'gender' => GenderEnum::class,
    ];

    public function scopeFilter(Builder $builder, QueryFilter $filters)
    {
        return $filters->apply($builder);
    }
}
