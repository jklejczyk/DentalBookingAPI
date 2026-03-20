<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Filters\V1\DentistFilter;
use App\Http\Requests\Api\V1\Dentist\StoreDentistRequest;
use App\Http\Requests\Api\V1\Dentist\UpdateDentistRequest;
use App\Http\Resources\V1\DentistResource;
use App\Models\Dentist;
use App\Traits\ApiResponses;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DentistController extends Controller
{
    use ApiResponses;

    public function index(DentistFilter $filters)
    {
        return DentistResource::collection(Dentist::filter($filters)->paginate());
    }

    public function show($appointmentTypeId)
    {
        try {
            $appointmentType = Dentist::findOrFail($appointmentTypeId);

            return new DentistResource($appointmentType);
        } catch (ModelNotFoundException $exception) {
            return $this->error('Dentist cannot be found.', 404);
        }
    }

    public function store(StoreDentistRequest $request)
    {
        $appointmentType = Dentist::create($request->mappedAttributes());

        return new DentistResource($appointmentType);
    }

    public function update(UpdateDentistRequest $request, $appointmentTypeId)
    {
        try {
            $appointmentType = Dentist::findOrFail($appointmentTypeId);
            $appointmentType->update($request->mappedAttributes());

            return new DentistResource($appointmentType);
        } catch (ModelNotFoundException $exception) {
            return $this->error('Dentist cannot be found.', 404);
        }
    }

    public function destroy($appointmentTypeId)
    {
        try {
            $appointmentType = Dentist::findOrFail($appointmentTypeId);
            $appointmentType->delete();

            return $this->ok('Dentist successfully deleted');
        } catch (ModelNotFoundException $exception) {
            return $this->error('Dentist cannot found.', 404);
        }
    }
}
