<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\HotelRequest;
use App\Http\Resources\HotelResource;
use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HotelController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $hotels = Hotel::withCount('rooms')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($request->integer('per_page', 10));

        $hotels->setCollection($hotels->getCollection()->map(fn ($hotel) => (new HotelResource($hotel))->resolve()));

        return $this->success($hotels);
    }

    public function store(HotelRequest $request)
    {
        if ($request->user()->role !== 'admin') {
            return $this->error('Forbidden', 403);
        }

        $data = $request->validated();
        $data['thumbnail'] = $request->file('thumbnail')->store('hotels', 'public');
        $hotel = Hotel::create($data);

        return $this->success(new HotelResource($hotel), 'Hotel created', 201);
    }

    public function show(Hotel $hotel)
    {
        $hotel->load([
            'rooms' => fn ($query) => $query->with(['images', 'facilities'])->withAvg('reviews', 'rating'),
        ]);

        return $this->success(new HotelResource($hotel));
    }

    public function update(HotelRequest $request, Hotel $hotel)
    {
        if ($request->user()->role !== 'admin') {
            return $this->error('Forbidden', 403);
        }

        $data = $request->validated();

        if ($request->hasFile('thumbnail')) {
            Storage::disk('public')->delete($hotel->thumbnail);
            $data['thumbnail'] = $request->file('thumbnail')->store('hotels', 'public');
        }

        $hotel->update($data);

        return $this->success(new HotelResource($hotel), 'Hotel updated');
    }

    public function destroy(Request $request, Hotel $hotel)
    {
        if ($request->user()->role !== 'admin') {
            return $this->error('Forbidden', 403);
        }

        Storage::disk('public')->delete($hotel->thumbnail);
        $hotel->delete();

        return $this->success(null, 'Hotel deleted');
    }
}
