<?php

namespace App\Http\Filters\V1;


class DentistFilter extends QueryFilter
{
    protected array $sortable = [
        'id',
        'first_name',
        'last_name',
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


    public function first_name($value)
    {
        return $this->builder->whereLike('first_name', '%'.$value.'%');
    }

    public function last_name($value)
    {
        return $this->builder->whereLike('last_name', '%'.$value.'%');
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
