<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Enums\AppointmentStatusEnum;
use App\Http\Filters\V1\AppointmentFilter;
use App\Http\Requests\Api\V1\Appointment\StoreAppointmentRequest;
use App\Http\Requests\Api\V1\Appointment\UpdateAppointmentRequest;
use App\Http\Resources\V1\AppointmentResource;
use App\Mail\AppointmentStatusChanged;
use App\Models\Appointment;
use App\Services\AppointmentService;
use App\Traits\ApiResponses;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Mail;

class AppointmentController extends Controller
{
    use ApiResponses;

    private $appointmentService;

    public function __construct(AppointmentService $appointmentService)
    {
        $this->appointmentService = $appointmentService;
    }

    public function index(AppointmentFilter $filters)
    {
        return AppointmentResource::collection(Appointment::checkVisibleForUser()->filter($filters)->paginate());
    }

    public function show($appointmentId)
    {
        try {
            $appointment = Appointment::findOrFail($appointmentId);
            $this->authorize('view', $appointment);

            return new AppointmentResource($appointment);
        } catch (ModelNotFoundException $exception) {
            return $this->error('Appointment cannot be found.', 404);
        }
    }

    public function store(StoreAppointmentRequest $request)
    {
        $appointment = $this->appointmentService->create($request->mappedAttributes());

        return new AppointmentResource($appointment);
    }

    public function update(UpdateAppointmentRequest $request, $appointmentId)
    {
        try {
            $appointment = Appointment::findOrFail($appointmentId);
            $appointment = $this->appointmentService->update($appointment, $request->mappedAttributes());

            return new AppointmentResource($appointment);
        } catch (ModelNotFoundException $exception) {
            return $this->error('Appointment cannot be found.', 404);
        }
    }

    public function destroy($appointmentId)
    {
        try {
            $appointment = Appointment::findOrFail($appointmentId);
            $appointment->delete();

            return $this->ok('Appointment successfully deleted');
        } catch (ModelNotFoundException $exception) {
            return $this->error('Appointment cannot found.', 404);
        }
    }

    public function confirm($appointmentId)
    {
        try {
            $appointment = Appointment::findOrFail($appointmentId);
            $this->authorize('changeStatus', $appointment);
            $appointment = $this->appointmentService->transition($appointment, AppointmentStatusEnum::CONFIRMED);

            $patient = $appointment->patient;
            if ($patient->email) {
                Mail::to($patient->email)->send(new AppointmentStatusChanged($appointment));
            }

            return new AppointmentResource($appointment);
        } catch (ModelNotFoundException $exception) {
            return $this->error('Appointment cannot found.', 404);
        }
    }

    public function cancel($appointmentId)
    {
        try {
            $appointment = Appointment::findOrFail($appointmentId);
            $this->authorize('changeStatus', $appointment);
            $appointment = $this->appointmentService->transition($appointment, AppointmentStatusEnum::CANCELLED);

            $patient = $appointment->patient;
            if ($patient->email) {
                Mail::to($patient->email)->send(new AppointmentStatusChanged($appointment));
            }

            return new AppointmentResource($appointment);
        } catch (ModelNotFoundException $exception) {
            return $this->error('Appointment cannot found.', 404);
        }
    }

    public function complete($appointmentId)
    {
        try {
            $appointment = Appointment::findOrFail($appointmentId);
            $this->authorize('changeStatus', $appointment);
            $appointment = $this->appointmentService->transition($appointment, AppointmentStatusEnum::COMPLETED);

            $patient = $appointment->patient;
            if ($patient->email) {
                Mail::to($patient->email)->send(new AppointmentStatusChanged($appointment));
            }

            return new AppointmentResource($appointment);
        } catch (ModelNotFoundException $exception) {
            return $this->error('Appointment cannot found.', 404);
        }
    }
}
