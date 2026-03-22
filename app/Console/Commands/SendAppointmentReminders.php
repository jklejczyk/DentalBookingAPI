<?php

namespace App\Console\Commands;

use App\Http\Enums\AppointmentStatusEnum;
use App\Mail\AppointmentReminder;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders';
    protected $description = 'Wysyła przypomnienia o wizytach zaplanowanych na jutro';

    public function handle(): int
    {
        $tomorrow = Carbon::tomorrow();

        $appointments = Appointment::whereDate('start', $tomorrow)
            ->whereIn('status', [AppointmentStatusEnum::BOOKED, AppointmentStatusEnum::CONFIRMED])
            ->with('patient')
            ->get();

        $sent = 0;

        foreach ($appointments as $appointment) {
            if ($appointment->patient->email) {
                Mail::to($appointment->patient->email)->send(new AppointmentReminder($appointment));
                $sent++;
            }
        }

        $this->info("Wysłano przypomnień: {$sent}.");

        return Command::SUCCESS;
    }
}
