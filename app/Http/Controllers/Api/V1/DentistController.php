<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Filters\V1\DentistFilter;
use App\Http\Requests\Api\V1\Dentist\DentistAvailabilityRequest;
use App\Http\Requests\Api\V1\Dentist\StoreDentistRequest;
use App\Http\Requests\Api\V1\Dentist\UpdateDentistRequest;
use App\Http\Resources\V1\DentistResource;
use App\Models\Dentist;
use App\Services\DentistAvailabilityService;
use App\Traits\ApiResponses;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DentistController extends Controller
{
    use ApiResponses;

    protected DentistAvailabilityService $dentistAvailabilityService;
    public function __construct(DentistAvailabilityService $dentistAvailabilityService)
    {
        $this->dentistAvailabilityService = $dentistAvailabilityService;
    }

    public function index(DentistFilter $filters)
    {
        return DentistResource::collection(Dentist::filter($filters)->paginate());
    }

    public function show($dentistId)
    {
        try {
            $dentist = Dentist::findOrFail($dentistId);

            return new DentistResource($dentist);
        } catch (ModelNotFoundException $exception) {
            return $this->error('Dentist cannot be found.', 404);
        }
    }

    public function store(StoreDentistRequest $request)
    {
        $dentist = Dentist::create($request->mappedAttributes());

        return new DentistResource($dentist);
    }

    public function update(UpdateDentistRequest $request, $dentistId)
    {
        try {
            $dentist = Dentist::findOrFail($dentistId);
            $dentist->update($request->mappedAttributes());

            return new DentistResource($dentist);
        } catch (ModelNotFoundException $exception) {
            return $this->error('Dentist cannot be found.', 404);
        }
    }

    public function destroy($dentistId)
    {
        try {
            $dentist = Dentist::findOrFail($dentistId);
            $dentist->delete();

            return $this->ok('Dentist successfully deleted');
        } catch (ModelNotFoundException $exception) {
            return $this->error('Dentist cannot found.', 404);
        }
    }

    public function availability(DentistAvailabilityRequest $request, $dentistId)
    {
        try {
            $dentist = Dentist::findOrFail($dentistId);
            $payload = $request->validated();

            $slots = $this->dentistAvailabilityService->getAvailableSlots($dentist, $payload['date'], $payload['appointment_type_id']);

            return response()->json(['data' => $slots]);
        } catch (ModelNotFoundException $exception) {
            return $this->error('Dentist cannot be found.', 404);
        }
    }
}
