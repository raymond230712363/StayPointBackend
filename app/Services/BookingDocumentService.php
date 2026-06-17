<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BookingDocumentService
{
    public function generateQrCode(Booking $booking): string
    {
        $hash = md5($booking->booking_code);
        $cells = '';

        for ($y = 0; $y < 12; $y++) {
            for ($x = 0; $x < 12; $x++) {
                $index = ($x + $y * 12) % strlen($hash);
                if (hexdec($hash[$index]) % 2 === 0 || $x < 2 || $y < 2 || $x > 9 || $y > 9) {
                    $cells .= '<rect x="'.($x * 10).'" y="'.($y * 10).'" width="10" height="10" fill="#111"/>';
                }
            }
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="160" height="190" viewBox="0 0 160 190">'
            .'<rect width="160" height="190" fill="#fff"/>'
            .'<g transform="translate(20 20)">'.$cells.'</g>'
            .'<text x="80" y="168" text-anchor="middle" font-size="12" font-family="Arial">'.e($booking->booking_code).'</text>'
            .'</svg>';

        $path = 'bookings/qr/'.$booking->booking_code.'.svg';
        Storage::disk('public')->put($path, $svg);

        return $path;
    }

    public function generateReceipt(Booking $booking): string
    {
        $booking->loadMissing('user', 'room.hotel', 'addons');
        $lines = [
            'StayPoint Booking Receipt',
            'Code: '.$booking->booking_code,
            'Customer: '.$booking->user->name,
            'Hotel: '.$booking->room->hotel->name,
            'Room: '.$booking->room->room_name,
            'Check In: '.$booking->check_in->toDateString(),
            'Check Out: '.$booking->check_out->toDateString(),
            'Total Nights: '.$booking->total_nights,
            'Total Price: '.$booking->total_price,
            'Payment Status: '.$booking->payment_status,
            'Status: '.$booking->status,
        ];

        $text = collect($lines)
            ->map(fn ($line) => '('.str_replace(['\\', '(', ')'], ['\\\\', '\(', '\)'], $line).') Tj T*')
            ->implode("\n");
        $stream = "BT /F1 12 Tf 50 780 Td 16 TL\n{$text}\nET";
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            "<< /Length ".strlen($stream)." >>\nstream\n{$stream}\nendstream",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $number = $index + 1;
            $pdf .= "{$number} 0 obj\n{$object}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= str_pad((string) $offsets[$i], 10, '0', STR_PAD_LEFT)." 00000 n \n";
        }

        $pdf .= "trailer << /Root 1 0 R /Size ".(count($objects) + 1)." >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        $path = 'bookings/receipts/'.$booking->booking_code.'-'.Str::random(6).'.pdf';
        Storage::disk('public')->put($path, $pdf);

        return $path;
    }
}
