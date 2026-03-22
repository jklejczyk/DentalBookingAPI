<?php

namespace App\Models;

use App\Http\Enums\AppointmentStatusEnum;
use App\Http\Filters\V1\QueryFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    /** @use HasFactory<\Database\Factories\AppointmentFactory> */
    use HasFactory;

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
        if (auth()->user()->isDentist()) {
            return $builder->where('dentist_id', auth()->user()->dentist->id);
        }

        return $builder;
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function dentist()
    {
        return $this->belongsTo(Dentist::class, 'dentist_id');
    }

    public function appointment_type()
    {
        return $this->belongsTo(AppointmentType::class, 'appointment_type_id');
    }
}
