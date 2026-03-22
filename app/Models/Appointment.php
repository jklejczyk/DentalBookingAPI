<?php

namespace App\Models;

use App\Http\Enums\AppointmentStatusEnum;
use App\Http\Filters\V1\QueryFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    /** @use HasFactory<\Database\Factories\AppointmentFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'appointments';

    protected $fillable = ['status', 'start', 'end', 'patient_id', 'dentist_id', 'appointment_type_id'];

    protected $casts = [
        'status' => AppointmentStatusEnum::class,
        'start' => 'datetime',
        'end' => 'datetime',
    ];

    public function scopeFilter(Builder $builder, QueryFilter $filters)
    {
        return $filters->apply($builder);
    }

    public function scopeCheckVisibleForUser(Builder $builder): Builder
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->isDentist()) {
            return $builder->where('dentist_id', $user->dentist?->id);
        }

        return $builder;
    }

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    /** @return BelongsTo<Dentist, $this> */
    public function dentist(): BelongsTo
    {
        return $this->belongsTo(Dentist::class, 'dentist_id');
    }

    /** @return BelongsTo<AppointmentType, $this> */
    public function appointment_type(): BelongsTo
    {
        return $this->belongsTo(AppointmentType::class, 'appointment_type_id');
    }
}
