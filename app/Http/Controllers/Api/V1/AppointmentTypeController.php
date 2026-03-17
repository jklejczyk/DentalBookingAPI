<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Filters\V1\AppointmentTypeFilter;
use App\Http\Requests\Api\V1\AppointmentType\StoreAppointmentTypeRequest;
use App\Http\Requests\Api\V1\AppointmentType\UpdateAppointmentTypeRequest;
use App\Http\Resources\V1\AppointmentTypeResource;
use App\Models\AppointmentType;
use App\Traits\ApiResponses;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class AppointmentTypeController extends Controller
{
    use ApiResponses;

    public function index(AppointmentTypeFilter $filters)
    {
        return AppointmentTypeResource::collection(AppointmentType::filter($filters)->paginate());
    }

    public function show($appointmentTypeId)
    {
        try {
            $appointmentType = AppointmentType::findOrFail($appointmentTypeId);

            return new AppointmentTypeResource($appointmentType);
        } catch (ModelNotFoundException $exception) {
            return $this->error('Appointment type cannot be found.', 404);
        }
    }

    public function store(StoreAppointmentTypeRequest $request)
    {
        $appointmentType = AppointmentType::create($request->mappedAttributes());

        return new AppointmentTypeResource($appointmentType);
    }

    public function update(UpdateAppointmentTypeRequest $request, $appointmentTypeId)
    {
        try {
            $appointmentType = AppointmentType::findOrFail($appointmentTypeId);
            $appointmentType->update($request->mappedAttributes());

            return new AppointmentTypeResource($appointmentType);
        } catch (ModelNotFoundException $exception) {
            return $this->error('Appointment type cannot be found.', 404);
        }
    }

    public function destroy($appointmentTypeId)
    {
        try {
            $appointmentType = AppointmentType::findOrFail($appointmentTypeId);
            $appointmentType->delete();

            return $this->ok('Appointment type successfully deleted');
        } catch (ModelNotFoundException $exception) {
            return $this->error('Appointment type cannot found.', 404);
        }
    }
}
