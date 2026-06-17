<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\FacilityRequest;
use App\Http\Resources\FacilityResource;
use App\Models\Facility;
use Illuminate\Http\Request;

class FacilityController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $facilities = Facility::when($request->search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->latest()
            ->paginate($request->integer('per_page', 10));
        $facilities->setCollection($facilities->getCollection()->map(fn ($facility) => (new FacilityResource($facility))->resolve()));

        return $this->success($facilities);
    }

    public function store(FacilityRequest $request)
    {
        if ($request->user()->role !== 'admin') {
            return $this->error('Forbidden', 403);
        }

        return $this->success(new FacilityResource(Facility::create($request->validated())), 'Facility created', 201);
    }

    public function show(Facility $facility)
    {
        return $this->success(new FacilityResource($facility));
    }

    public function update(FacilityRequest $request, Facility $facility)
    {
        if ($request->user()->role !== 'admin') {
            return $this->error('Forbidden', 403);
        }

        $facility->update($request->validated());

        return $this->success(new FacilityResource($facility), 'Facility updated');
    }

    public function destroy(Request $request, Facility $facility)
    {
        if ($request->user()->role !== 'admin') {
            return $this->error('Forbidden', 403);
        }

        $facility->delete();

        return $this->success(null, 'Facility deleted');
    }
}
