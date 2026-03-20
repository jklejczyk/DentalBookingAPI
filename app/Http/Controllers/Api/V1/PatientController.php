<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Filters\V1\PatientFilter;
use App\Http\Requests\Api\V1\Patient\StorePatientRequest;
use App\Http\Requests\Api\V1\Patient\UpdatePatientRequest;
use App\Http\Resources\V1\PatientResource;
use App\Models\Patient;
use App\Traits\ApiResponses;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PatientController extends Controller
{
    use ApiResponses;

    public function index(PatientFilter $filters)
    {
        return PatientResource::collection(Patient::filter($filters)->paginate());
    }

    public function show($appointmentTypeId)
    {
        try {
            $appointmentType = Patient::findOrFail($appointmentTypeId);

            return new PatientResource($appointmentType);
        } catch (ModelNotFoundException $exception) {
            return $this->error('Patient cannot be found.', 404);
        }
    }

    public function store(StorePatientRequest $request)
    {
        $appointmentType = Patient::create($request->mappedAttributes());

        return new PatientResource($appointmentType);
    }

    public function update(UpdatePatientRequest $request, $appointmentTypeId)
    {
        try {
            $appointmentType = Patient::findOrFail($appointmentTypeId);
            $appointmentType->update($request->mappedAttributes());

            return new PatientResource($appointmentType);
        } catch (ModelNotFoundException $exception) {
            return $this->error('Patient cannot be found.', 404);
        }
    }

    public function destroy($appointmentTypeId)
    {
        try {
            $appointmentType = Patient::findOrFail($appointmentTypeId);
            $appointmentType->delete();

            return $this->ok('Patient successfully deleted');
        } catch (ModelNotFoundException $exception) {
            return $this->error('Patient cannot found.', 404);
        }
    }
}
