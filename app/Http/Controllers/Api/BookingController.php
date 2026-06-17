<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\BookingRequest;
use App\Http\Requests\Api\BookingStatusRequest;
use App\Http\Resources\BookingResource;
use App\Models\Addon;
use App\Models\Booking;
use App\Models\Room;
use App\Services\BookingDocumentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    use ApiResponse;

    public function __construct(private BookingDocumentService $documents)
    {
    }

    public function store(BookingRequest $request)
    {
        $booking = DB::transaction(function () use ($request) {
            $room = Room::lockForUpdate()->findOrFail($request->room_id);

            if ($room->stock < 1) {
                throw ValidationException::withMessages([
                    'room_id' => ['Room stock is not available.'],
                ]);
            }

            $checkIn = Carbon::parse($request->check_in);
            $checkOut = Carbon::parse($request->check_out);
            $totalNights = $checkIn->diffInDays($checkOut);
            $addonsTotal = 0;
            $syncAddons = [];

            foreach ($request->input('addons', []) as $item) {
                $addon = Addon::findOrFail($item['id']);
                $subtotal = $addon->price * $item['quantity'];
                $addonsTotal += $subtotal;
                $syncAddons[$addon->id] = [
                    'quantity' => $item['quantity'],
                    'subtotal' => $subtotal,
                ];
            }

            $booking = Booking::create([
                'booking_code' => $this->generateBookingCode(),
                'user_id' => $request->user()->id,
                'room_id' => $room->id,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'total_nights' => $totalNights,
                'total_price' => ($room->price_per_night * $totalNights) + $addonsTotal,
                'payment_status' => 'pending',
                'status' => 'pending',
            ]);

            $booking->addons()->sync($syncAddons);
            $room->decrement('stock');

            return $booking;
        });

        $booking->update([
            'qr_code' => $this->documents->generateQrCode($booking),
            'pdf_receipt' => $this->documents->generateReceipt($booking),
        ]);

        return $this->success(new BookingResource($booking->load(['user', 'room.hotel', 'addons'])), 'Booking created', 201);
    }

    public function show(Request $request, Booking $booking)
    {
        if (!$this->canAccess($request, $booking)) {
            return $this->error('Forbidden', 403);
        }

        return $this->success(new BookingResource($booking->load(['user', 'room.hotel', 'addons', 'review'])));
    }

    public function history(Request $request)
    {
        $bookings = Booking::with(['room.hotel', 'addons', 'review'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate($request->integer('per_page', 10));
        $bookings->setCollection($bookings->getCollection()->map(fn ($booking) => (new BookingResource($booking))->resolve()));

        return $this->success($bookings);
    }

    public function adminIndex(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return $this->error('Forbidden', 403);
        }

        $bookings = Booking::with(['user', 'room.hotel', 'addons'])
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->when($request->payment_status, fn ($query, $status) => $query->where('payment_status', $status))
            ->latest()
            ->paginate($request->integer('per_page', 10));
        $bookings->setCollection($bookings->getCollection()->map(fn ($booking) => (new BookingResource($booking))->resolve()));

        return $this->success($bookings);
    }

    public function updateStatus(BookingStatusRequest $request, Booking $booking)
    {
        if ($request->user()->role !== 'admin') {
            return $this->error('Forbidden', 403);
        }

        $booking->update($request->validated());

        return $this->success(new BookingResource($booking->load(['user', 'room.hotel', 'addons'])), 'Booking status updated');
    }

    public function cancel(Request $request, Booking $booking)
    {
        if (!$this->canAccess($request, $booking)) {
            return $this->error('Forbidden', 403);
        }

        if ($booking->status === 'completed') {
            return $this->error('Completed booking cannot be cancelled', 422);
        }

        DB::transaction(function () use ($booking) {
            if ($booking->status !== 'cancelled') {
                $booking->room()->increment('stock');
            }

            $booking->update([
                'status' => 'cancelled',
                'payment_status' => 'cancelled',
            ]);
        });

        return $this->success(new BookingResource($booking->refresh()->load(['room.hotel', 'addons'])), 'Booking cancelled');
    }

    private function generateBookingCode(): string
    {
        do {
            $code = 'SP-'.now()->format('Ymd').'-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (Booking::where('booking_code', $code)->exists());

        return $code;
    }

    private function canAccess(Request $request, Booking $booking): bool
    {
        return $request->user()->role === 'admin' || $booking->user_id === $request->user()->id;
    }
}
