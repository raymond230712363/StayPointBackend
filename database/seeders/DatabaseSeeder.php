<?php

namespace Database\Seeders;

use App\Models\Addon;
use App\Models\Facility;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@staypoint.test'],
            [
                'name' => 'StayPoint Admin',
                'password' => Hash::make('password'),
                'phone' => '081234567890',
                'role' => 'admin',
            ]
        );

        User::updateOrCreate(
            ['email' => 'customer@staypoint.test'],
            [
                'name' => 'StayPoint Customer',
                'password' => Hash::make('password'),
                'phone' => '081111111111',
                'role' => 'customer',
            ]
        );

        $facilities = collect(['WiFi', 'Air Conditioner', 'Smart TV', 'Hot Shower', 'Workspace'])
            ->map(fn ($name) => Facility::firstOrCreate(['name' => $name]));

        $addons = [
            ['name' => 'Breakfast', 'price' => 75000],
            ['name' => 'Airport Pickup', 'price' => 180000],
            ['name' => 'Extra Bed', 'price' => 150000],
        ];

        foreach ($addons as $addon) {
            Addon::updateOrCreate(['name' => $addon['name']], $addon);
        }

        $hotel = Hotel::updateOrCreate(
            ['name' => 'StayPoint Central'],
            [
                'location' => 'Jakarta',
                'description' => 'Hotel nyaman di pusat kota dengan akses mudah ke transportasi umum.',
                'thumbnail' => 'hotels/sample-central.jpg',
            ]
        );

        $resort = Hotel::updateOrCreate(
            ['name' => 'StayPoint Riverside'],
            [
                'location' => 'Bandung',
                'description' => 'Penginapan tenang dengan pemandangan sungai dan udara sejuk.',
                'thumbnail' => 'hotels/sample-riverside.jpg',
            ]
        );

        $rooms = [
            [
                'hotel_id' => $hotel->id,
                'room_name' => 'Deluxe Queen',
                'description' => 'Kamar queen bed untuk dua tamu dengan fasilitas lengkap.',
                'capacity' => 2,
                'price_per_night' => 550000,
                'stock' => 8,
            ],
            [
                'hotel_id' => $hotel->id,
                'room_name' => 'Family Suite',
                'description' => 'Suite luas untuk keluarga dengan ruang duduk terpisah.',
                'capacity' => 4,
                'price_per_night' => 950000,
                'stock' => 4,
            ],
            [
                'hotel_id' => $resort->id,
                'room_name' => 'Riverside Cabin',
                'description' => 'Kabin privat dekat sungai untuk pengalaman menginap yang tenang.',
                'capacity' => 2,
                'price_per_night' => 700000,
                'stock' => 6,
            ],
        ];

        foreach ($rooms as $roomData) {
            $room = Room::updateOrCreate(
                ['hotel_id' => $roomData['hotel_id'], 'room_name' => $roomData['room_name']],
                $roomData
            );

            $room->facilities()->sync($facilities->pluck('id')->take(4)->all());
        }
    }
}
