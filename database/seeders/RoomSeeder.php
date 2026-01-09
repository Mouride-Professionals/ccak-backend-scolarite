<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = [
            ['room_number' => 'A101', 'name' => 'Amphi A', 'building' => 'Bloc A', 'capacity' => 120, 'type' => 'LECTURE_HALL', 'equipment' => ['projector', 'sound']],
            ['room_number' => 'A102', 'name' => 'Salle TD 1', 'building' => 'Bloc A', 'capacity' => 40, 'type' => 'TD_ROOM', 'equipment' => ['projector']],
            ['room_number' => 'B201', 'name' => 'Salle TD 2', 'building' => 'Bloc B', 'capacity' => 35, 'type' => 'TD_ROOM', 'equipment' => ['whiteboard']],
            ['room_number' => 'B202', 'name' => 'Laboratoire Info', 'building' => 'Bloc B', 'capacity' => 30, 'type' => 'LAB', 'equipment' => ['pc', 'projector']],
            ['room_number' => 'C301', 'name' => 'Amphi C', 'building' => 'Bloc C', 'capacity' => 150, 'type' => 'LECTURE_HALL', 'equipment' => ['projector', 'sound']],
            ['room_number' => 'C302', 'name' => 'Lab Réseaux', 'building' => 'Bloc C', 'capacity' => 25, 'type' => 'LAB', 'equipment' => ['pc', 'router']],
        ];

        foreach ($rooms as $room) {
            Room::firstOrCreate(
                ['room_number' => $room['room_number']],
                $room + ['is_available' => true]
            );
        }
    }
}
