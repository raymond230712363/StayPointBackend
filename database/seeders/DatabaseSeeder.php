<?php

namespace Database\Seeders;

use App\Models\Addon;
use App\Models\Facility;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\RoomImage;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::firstOrCreate(
    ['email' => 'test@example.com'],
    [
        'name' => 'Test User',
        'password' => bcrypt('password'),
    ]
);

        $wifi = Facility::firstOrCreate(['name' => 'Wifi']);
        $breakfast = Facility::firstOrCreate(['name' => 'Breakfast']);
        $pool = Facility::firstOrCreate(['name' => 'Pool']);

        Addon::firstOrCreate(['name' => 'Breakfast Package'], ['price' => 75000]);
        Addon::firstOrCreate(['name' => 'Airport Pickup'], ['price' => 150000]);
        Addon::firstOrCreate(['name' => 'Extra Bed'], ['price' => 120000]);

        $hotel = Hotel::firstOrCreate(
            ['name' => 'Hill Side Villa'],
            [
                'location' => 'Babarsari, Yogyakarta',
                'description' => 'Villa tenang dengan akses mudah ke pusat kota.',
                'thumbnail' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800&q=80',
            ]
        );

        $room = Room::firstOrCreate(
            ['hotel_id' => $hotel->id, 'room_name' => 'Standard Room'],
            [
                'description' => 'Kamar nyaman dengan queen bed, wifi, dan sarapan.',
                'capacity' => 2,
                'price_per_night' => 600000,
                'stock' => 6,
            ]
        );
        $room->facilities()->syncWithoutDetaching([$wifi->id, $breakfast->id, $pool->id]);
        RoomImage::firstOrCreate([
            'room_id' => $room->id,
            'image_url' => 'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=800&q=80',
        ]);
    }
}
