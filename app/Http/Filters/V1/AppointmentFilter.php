<?php

namespace App\Http\Filters\V1;


class AppointmentFilter extends QueryFilter
{
    protected $sortable = [
        'id',
        'start',
        'end',
        'status',
        'appointment_type_id',
        'createdAt' => 'created_at',
        'updatedAt' => 'updated_at'
    ];

    public function id($value)
    {
        $dates = explode(',', $value);

        if (count($dates) > 1) {
            return $this->builder->whereIn('id', $dates);
        }

        return $this->builder->where('id', $value);
    }

    public function dentist($value)
    {
        return $this->builder->where('dentist_id', $value);
    }

    public function patient($value)
    {
        return $this->builder->where('patient_id', $value);
    }

    public function appointment_type($value)
    {
        return $this->builder->where('appointment_type_id', $value);
    }

    public function start($value) {
        $dates = explode(',', $value);

        if (count($dates) > 1) {
            return $this->builder->whereBetween('start', $dates);
        }

        return $this->builder->whereDate('start', $value);
    }

    public function end($value) {
        $dates = explode(',', $value);

        if (count($dates) > 1) {
            return $this->builder->whereBetween('end', $dates);
        }

        return $this->builder->whereDate('end', $value);
    }

    public function createdAt($value) {
        $dates = explode(',', $value);

        if (count($dates) > 1) {
            return $this->builder->whereBetween('created_at', $dates);
        }

        return $this->builder->whereDate('created_at', $value);
    }

    public function updatedAt($value) {
        $dates = explode(',', $value);

        if (count($dates) > 1) {
            return $this->builder->whereBetween('updated_at', $dates);
        }

        return $this->builder->whereDate('updated_at', $value);
    }
}
