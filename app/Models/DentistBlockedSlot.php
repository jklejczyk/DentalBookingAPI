<?php

namespace App\Models;

use App\Http\Filters\V1\QueryFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DentistBlockedSlot extends Model
{
    /** @use HasFactory<\Database\Factories\DentistBlockedSlotFactory> */
    use HasFactory;

    protected $table = 'dentist_blocked_slots';

    protected $fillable = ['dentist_id', 'start', 'end', 'reason'];

    protected $casts = [
        'start' => 'datetime',
        'end' => 'datetime',
    ];

    public function dentist(): BelongsTo
    {
        return $this->belongsTo(Dentist::class, 'dentist_id');
    }

    public function scopeFilter(Builder $builder, QueryFilter $filters)
    {
        return $filters->apply($builder);
    }

    public function scopeForDentist(Builder $builder, Dentist $dentist): Builder
    {
        return $builder->where('dentist_id', $dentist->id);
    }
}
