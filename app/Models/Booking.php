<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    protected $fillable = [
        'booking_code', 'user_id', 'room_id', 'check_in', 'check_out', 
        'total_nights', 'total_price', 'payment_status', 'status', 'qr_code', 'pdf_receipt'
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    // Relasi Many-to-Many ke Addons via tabel pivot booking_addons
    public function addons(): BelongsToMany
    {
        return $this->belongsToMany(Addon::class, 'booking_addons')
                    ->withPivot('quantity', 'subtotal')
                    ->withTimestamps(); // Biar kolom tambahan di pivot bisa diakses
    }

    // Relasi ke Review (1 Booking maksimal punya 1 Review)
    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }
}
