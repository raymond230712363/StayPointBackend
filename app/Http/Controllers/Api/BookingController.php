<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Addon;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->userFromRequest($request);
        if (!$user) {
            return $this->userNotFound();
        }

        $query = Booking::with(['room.hotel', 'room.images', 'addons', 'review'])
            ->where('user_id', $user->id)
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'success' => true,
            'bookings' => $query->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required_without:user_id|email',
            'user_id' => 'required_without:email|exists:users,id',
            'room_id' => 'required|exists:rooms,id',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'payment_status' => 'sometimes|in:pending,paid,cancelled',
            'status' => 'sometimes|in:pending,paid,cancelled,completed',
            'addons' => 'nullable|array',
            'addons.*.id' => 'required_with:addons|exists:addons,id',
            'addons.*.quantity' => 'required_with:addons|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $user = $this->userFromRequest($request);
        if (!$user) {
            return $this->userNotFound();
        }

        $room = Room::findOrFail($request->room_id);
        if (!$this->roomHasStock($room, $request->check_in, $request->check_out)) {
            return response()->json(['success' => false, 'message' => 'Stock kamar tidak tersedia pada tanggal tersebut.'], 422);
        }

        [$totalNights, $roomTotal, $addonRows, $addonTotal] = $this->calculatePrice($room, $request);
        $bookingCode = $this->generateBookingCode();

        $booking = Booking::create([
            'booking_code' => $bookingCode,
            'user_id' => $user->id,
            'room_id' => $room->id,
            'check_in' => $request->check_in,
            'check_out' => $request->check_out,
            'total_nights' => $totalNights,
            'total_price' => $roomTotal + $addonTotal,
            'payment_status' => $request->input('payment_status', 'pending'),
            'status' => $request->input('status', 'pending'),
            'qr_code' => 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . urlencode($bookingCode),
        ]);

        $this->syncAddons($booking, $addonRows);
        $booking->pdf_receipt = $this->writeReceiptPdf($booking->fresh(['room.hotel', 'addons', 'user']));
        $booking->save();

        return response()->json([
            'success' => true,
            'message' => 'Booking berhasil dibuat',
            'booking' => $booking->load(['room.hotel', 'room.images', 'addons', 'review']),
        ], 201);
    }

    public function show(Request $request, Booking $booking)
    {
        $user = $this->userFromRequest($request);
        if (!$user || $booking->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Booking tidak ditemukan.'], 404);
        }

        return response()->json([
            'success' => true,
            'booking' => $booking->load(['room.hotel', 'room.images', 'addons', 'review']),
        ]);
    }

    public function update(Request $request, Booking $booking)
    {
        $user = $this->userFromRequest($request);
        if (!$user || $booking->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Booking tidak ditemukan.'], 404);
        }

        if ($booking->status === 'cancelled') {
            return response()->json(['success' => false, 'message' => 'Booking cancelled tidak bisa diubah.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'check_in' => 'sometimes|date|after_or_equal:today',
            'check_out' => 'sometimes|date|after:check_in',
            'payment_status' => 'sometimes|in:pending,paid,cancelled',
            'status' => 'sometimes|in:pending,paid,cancelled,completed',
            'addons' => 'nullable|array',
            'addons.*.id' => 'required_with:addons|exists:addons,id',
            'addons.*.quantity' => 'required_with:addons|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $checkIn = $request->input('check_in', $booking->check_in);
        $checkOut = $request->input('check_out', $booking->check_out);
        if (!$this->roomHasStock($booking->room, $checkIn, $checkOut, $booking->id)) {
            return response()->json(['success' => false, 'message' => 'Stock kamar tidak tersedia pada tanggal tersebut.'], 422);
        }

        $requestData = array_merge($request->all(), [
            'check_in' => $checkIn,
            'check_out' => $checkOut,
        ]);
        if (!$request->has('addons')) {
            $requestData['addons'] = $booking->addons->map(fn ($addon) => [
                'id' => $addon->id,
                'quantity' => $addon->pivot->quantity,
            ])->values()->all();
        }

        $fakeRequest = new Request($requestData);
        [$totalNights, $roomTotal, $addonRows, $addonTotal] = $this->calculatePrice($booking->room, $fakeRequest);

        $booking->fill([
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'total_nights' => $totalNights,
            'total_price' => $roomTotal + $addonTotal,
            'payment_status' => $request->input('payment_status', $booking->payment_status),
            'status' => $request->input('status', $booking->status),
        ]);
        $booking->save();

        if ($request->has('addons')) {
            $this->syncAddons($booking, $addonRows);
        }

        $booking->pdf_receipt = $this->writeReceiptPdf($booking->fresh(['room.hotel', 'addons', 'user']));
        $booking->save();

        return response()->json([
            'success' => true,
            'message' => 'Booking berhasil diperbarui',
            'booking' => $booking->load(['room.hotel', 'room.images', 'addons', 'review']),
        ]);
    }

    public function cancel(Request $request, Booking $booking)
    {
        $user = $this->userFromRequest($request);
        if (!$user || $booking->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Booking tidak ditemukan.'], 404);
        }

        if ($booking->status === 'completed') {
            return response()->json(['success' => false, 'message' => 'Booking completed tidak bisa dibatalkan.'], 422);
        }

        $booking->update([
            'payment_status' => 'cancelled',
            'status' => 'cancelled',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Booking berhasil dibatalkan',
            'booking' => $booking->load(['room.hotel', 'addons', 'review']),
        ]);
    }

    private function userFromRequest(Request $request): ?User
    {
        if ($request->filled('user_id')) {
            return User::find($request->user_id);
        }

        return User::where('email', $request->email)->first();
    }

    private function userNotFound()
    {
        return response()->json(['success' => false, 'message' => 'User tidak ditemukan.'], 404);
    }

    private function roomHasStock(Room $room, string $checkIn, string $checkOut, ?int $ignoreBookingId = null): bool
    {
        $activeBookingCount = Booking::where('room_id', $room->id)
            ->whereNotIn('status', ['cancelled'])
            ->when($ignoreBookingId, fn ($query) => $query->where('id', '!=', $ignoreBookingId))
            ->whereDate('check_in', '<', $checkOut)
            ->whereDate('check_out', '>', $checkIn)
            ->count();

        return $activeBookingCount < $room->stock;
    }

    private function calculatePrice(Room $room, Request $request): array
    {
        $checkIn = Carbon::parse($request->check_in);
        $checkOut = Carbon::parse($request->check_out);
        $totalNights = (int) max(1, $checkIn->diffInDays($checkOut));
        $roomTotal = $room->price_per_night * $totalNights;

        $addonRows = [];
        $addonTotal = 0;
        foreach ($request->input('addons', []) as $addonRequest) {
            $addon = Addon::find($addonRequest['id']);
            if (!$addon) {
                continue;
            }

            $quantity = (int) $addonRequest['quantity'];
            $subtotal = $addon->price * $quantity;
            $addonTotal += $subtotal;
            $addonRows[$addon->id] = [
                'quantity' => $quantity,
                'subtotal' => $subtotal,
            ];
        }

        return [$totalNights, $roomTotal, $addonRows, $addonTotal];
    }

    private function syncAddons(Booking $booking, array $addonRows): void
    {
        $booking->addons()->sync($addonRows);
    }

    private function generateBookingCode(): string
    {
        do {
            $code = 'SP' . now()->format('ymd') . Str::upper(Str::random(5));
        } while (Booking::where('booking_code', $code)->exists());

        return $code;
    }

    private function writeReceiptPdf(Booking $booking): string
    {
        $lines = [
            'StayPoint Booking Receipt',
            'Booking Code: ' . $booking->booking_code,
            'Guest: ' . $booking->user->name,
            'Hotel: ' . $booking->room->hotel->name,
            'Room: ' . $booking->room->room_name,
            'Check In: ' . $booking->check_in,
            'Check Out: ' . $booking->check_out,
            'Total Nights: ' . $booking->total_nights,
            'Total Price: Rp ' . number_format($booking->total_price, 0, ',', '.'),
            'Status: ' . $booking->status,
        ];

        $content = "BT /F1 16 Tf 50 780 Td (" . $this->pdfEscape($lines[0]) . ") Tj";
        foreach (array_slice($lines, 1) as $line) {
            $content .= " 0 -28 Td (" . $this->pdfEscape($line) . ") Tj";
        }
        $content .= " ET";

        $pdf = "%PDF-1.4\n";
        $objects = [
            "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n",
            "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n",
            "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >> endobj\n",
            "4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n",
            "5 0 obj << /Length " . strlen($content) . " >> stream\n$content\nendstream endobj\n",
        ];

        $offsets = [0];
        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        for ($i = 1; $i <= 5; $i++) {
            $pdf .= str_pad((string) $offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }
        $pdf .= "trailer << /Size 6 /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";

        $path = 'receipts/' . $booking->booking_code . '.pdf';
        Storage::disk('public')->put($path, $pdf);

        return Storage::url($path);
    }

    private function pdfEscape(string $value): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
    }
}
