<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Room extends Model
{
    protected $fillable = ['hotel_id', 'room_name', 'description', 'capacity', 'price_per_night', 'stock'];

    public function hotel(): BelongsTo
    {
        return $table->belongsTo(Hotel::class);
    }

    // Relasi ke foto-foto kamar
    public function images(): HasMany
    {
        return $this->hasMany(RoomImage::class);
    }

    // Relasi Many-to-Many ke Facilities via tabel pivot
    public function facilities(): BelongsToMany
    {
        return $this->belongsToMany(Facility::class, 'room_facilities');
    }
}
