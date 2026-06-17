<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AddonRequest;
use App\Http\Resources\AddonResource;
use App\Models\Addon;
use Illuminate\Http\Request;

class AddonController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $addons = Addon::when($request->search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->latest()
            ->paginate($request->integer('per_page', 10));
        $addons->setCollection($addons->getCollection()->map(fn ($addon) => (new AddonResource($addon))->resolve()));

        return $this->success($addons);
    }

    public function store(AddonRequest $request)
    {
        if ($request->user()->role !== 'admin') {
            return $this->error('Forbidden', 403);
        }

        return $this->success(new AddonResource(Addon::create($request->validated())), 'Addon created', 201);
    }

    public function show(Addon $addon)
    {
        return $this->success(new AddonResource($addon));
    }

    public function update(AddonRequest $request, Addon $addon)
    {
        if ($request->user()->role !== 'admin') {
            return $this->error('Forbidden', 403);
        }

        $addon->update($request->validated());

        return $this->success(new AddonResource($addon), 'Addon updated');
    }

    public function destroy(Request $request, Addon $addon)
    {
        if ($request->user()->role !== 'admin') {
            return $this->error('Forbidden', 403);
        }

        $addon->delete();

        return $this->success(null, 'Addon deleted');
    }
}
