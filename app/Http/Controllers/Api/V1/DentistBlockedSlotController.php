<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Filters\V1\DentistFilter;
use App\Http\Requests\Api\V1\Dentist\DentistAvailabilityRequest;
use App\Http\Requests\Api\V1\Dentist\StoreDentistRequest;
use App\Http\Requests\Api\V1\Dentist\UpdateDentistRequest;
use App\Http\Requests\Api\V1\DentistBlockedSlot\StoreDentistBlockedSlotRequest;
use App\Http\Resources\V1\DentistBlockedSlotResource;
use App\Http\Resources\V1\DentistResource;
use App\Models\Dentist;
use App\Models\DentistBlockedSlot;
use App\Services\DentistAvailabilityService;
use App\Traits\ApiResponses;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DentistBlockedSlotController extends Controller
{
    use ApiResponses;

    public function index(DentistFilter $filters, int $dentistId)
    {
        try {
            $dentist = Dentist::findOrFail($dentistId);

            return DentistBlockedSlotResource::collection(DentistBlockedSlot::forDentist($dentist)->filter($filters)->paginate());
        } catch (ModelNotFoundException $exception) {
            return $this->error('Dentist cannot found.', 404);
        }
    }

    public function store(StoreDentistBlockedSlotRequest $request, int $dentistId)
    {
        try {
            $dentist = Dentist::findOrFail($dentistId);

            $dentistBlockedSlot = DentistBlockedSlot::create(
                array_merge($request->mappedAttributes(), ['dentist_id' => $dentist->id])
            );

            return new DentistBlockedSlotResource($dentistBlockedSlot);
        } catch (ModelNotFoundException $exception) {
            return $this->error('Dentist cannot be found.', 404);
        }
    }

    public function destroy(int $dentistId, int $dentistBlockedSlotId)
    {
        try {
            $dentist = Dentist::findOrFail($dentistId);
            $dentistBlockedSlot = DentistBlockedSlot::forDentist($dentist)->findOrFail($dentistBlockedSlotId);
            $dentistBlockedSlot->delete();

            return $this->ok('Dentist blocked slot successfully deleted');
        } catch (ModelNotFoundException $exception) {
            return $this->error('Dentist blocked slot cannot found.', 404);
        }
    }
}
