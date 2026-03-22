<?php

namespace App\Services;

use App\Http\Enums\AppointmentStatusEnum;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Dentist;
use Carbon\Carbon;

class DentistAvailabilityService
{
    private const START_HOUR = 8;
    private const END_HOUR = 18;
    private const WORKING_DAYS = [1, 2, 3, 4, 5]; // pon-pt

    public function getAvailableSlots(Dentist $dentist, string $date, int $appointmentTypeId): array
    {
        $appointmentType = AppointmentType::findOrFail($appointmentTypeId);
        $durationMinutes = $appointmentType->duration_minutes;
        $date = Carbon::parse($date);

        if (!in_array($date->dayOfWeekIso, self::WORKING_DAYS)) {
            return [];
        }

        $appointments = Appointment::where('dentist_id', $dentist->id)
            ->whereDate('start', $date)
            ->where('status', '!=', AppointmentStatusEnum::CANCELLED)
            ->get();

        $slots = [];
        $slotStart = Carbon::parse($date)->setTime(self::START_HOUR, 0);
        $dayEnd = Carbon::parse($date)->setTime(self::END_HOUR, 0);

        while (Carbon::parse($slotStart)->addMinutes($durationMinutes)->lte($dayEnd)) {
            $slotEnd = Carbon::parse($slotStart)->addMinutes($durationMinutes);

            $isOccupied = $appointments->contains(function ($appointment) use ($slotStart, $slotEnd) {
                return $slotStart->lt($appointment->end) && $slotEnd->gt($appointment->start);
            });

            if (!$isOccupied) {
                $slots[] = [
                    'start' => $slotStart->format('Y-m-d H:i'),
                    'end' => $slotEnd->format('Y-m-d H:i'),
                ];
            }

            $slotStart->addMinutes($durationMinutes);
        }

        return $slots;
    }
}
