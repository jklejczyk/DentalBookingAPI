<?php

namespace App\Services;

use App\Http\Enums\AppointmentStatusEnum;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    public function create(array $data): Appointment
    {
        return DB::transaction(function () use ($data) {
            $this->checkForConflict($data['dentist_id'], $data['start'], $data['end']);

            return Appointment::create($data);
        });
    }

    public function update(Appointment $appointment, array $data): Appointment
    {
        return DB::transaction(function () use ($appointment, $data) {
            $dentistId = $data['dentist_id'] ?? $appointment->dentist_id;
            $start = $data['start'] ?? $appointment->start;
            $end = $data['end'] ?? $appointment->end;

            $this->checkForConflict($dentistId, $start, $end, $appointment->id);

            $appointment->update($data);

            return $appointment;
        });
    }

    private function checkForConflict(int|string $dentistId, string $start, string $end, ?int $excludeId = null): void
    {
        $query = Appointment::where('dentist_id', $dentistId)
            ->where('status', '!=', AppointmentStatusEnum::CANCELLED)
            ->where('start', '<', $end)
            ->where('end', '>', $start)
            ->lockForUpdate();

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'data.attributes.start' => 'Ten termin koliduje z istniejącą wizytą.',
            ]);
        }
    }
}