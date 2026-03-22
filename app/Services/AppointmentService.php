<?php

namespace App\Services;

use App\Http\Enums\AppointmentStatusEnum;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    private const ALLOWED_TRANSITIONS = [
        'booked' => [AppointmentStatusEnum::CONFIRMED->value, AppointmentStatusEnum::CANCELLED->value],
        'confirmed' => [AppointmentStatusEnum::COMPLETED->value, AppointmentStatusEnum::CANCELLED->value],
        'completed' => [],
        'cancelled' => [],
    ];

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

    public function transition(Appointment $appointment, AppointmentStatusEnum $newStatus): Appointment
    {
        $currentStatus = $appointment->status->value;
        $allowedStatuses = self::ALLOWED_TRANSITIONS[$currentStatus];

        if (!in_array($newStatus->value, $allowedStatuses)) {
            throw ValidationException::withMessages([
                'status' => "Nie można zmienić statusu z '{$currentStatus}' na '{$newStatus->value}'.",
            ]);
        }

        if ($newStatus === AppointmentStatusEnum::CANCELLED
            && $appointment->status === AppointmentStatusEnum::CONFIRMED
            && Carbon::now()->diffInHours($appointment->start) < 24
        ) {
            throw ValidationException::withMessages([
                'status' => 'Nie można anulować potwierdzonej wizyty na mniej niż 24h przed terminem.',
            ]);
        }

        $appointment->status = $newStatus;
        $appointment->save();

        return $appointment;
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
