<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Filters\V1\AppointmentFilter;
use App\Http\Requests\Api\V1\Appointment\StoreAppointmentRequest;
use App\Http\Requests\Api\V1\Appointment\UpdateAppointmentRequest;
use App\Http\Resources\V1\AppointmentResource;
use App\Models\Appointment;
use App\Traits\ApiResponses;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AppointmentController extends Controller
{
    use ApiResponses;

    public function index(AppointmentFilter $filters)
    {
        return AppointmentResource::collection(Appointment::filter($filters)->paginate());
    }

    public function show($appointmentId)
    {
        try {
            $appointment = Appointment::findOrFail($appointmentId);

            return new AppointmentResource($appointment);
        } catch (ModelNotFoundException $exception) {
            return $this->error('Appointment cannot be found.', 404);
        }
    }

    public function store(StoreAppointmentRequest $request)
    {
        $appointment = Appointment::create($request->mappedAttributes());

        return new AppointmentResource($appointment);
    }

    public function update(UpdateAppointmentRequest $request, $appointmentId)
    {
        try {
            $appointment = Appointment::findOrFail($appointmentId);
            $appointment->update($request->mappedAttributes());

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
}
