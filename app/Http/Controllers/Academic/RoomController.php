<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Academic\StoreRoomRequest;
use App\Http\Requests\Academic\UpdateRoomRequest;
use App\Http\Resources\Academic\RoomResource;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class RoomController extends BaseApiController
{
    public function __construct()
    {
        $this->middleware('permission:rooms.view')->only(['index']);
        $this->middleware('permission:rooms.create')->only('store');
        $this->middleware('permission:rooms.update')->only('update');
        $this->middleware('permission:rooms.delete')->only('destroy');
    }

    public function index(Request $request): JsonResponse
    {
        $rooms = QueryBuilder::for(Room::query())
            ->allowedFilters([
                AllowedFilter::exact('building'),
                AllowedFilter::exact('type'),
                AllowedFilter::scope('is_available'),
                AllowedFilter::scope('search'),
            ])
            ->allowedSorts(['room_number', 'name', 'capacity', 'created_at'])
            ->defaultSort('room_number')
            ->paginate($request->integer('per_page') ?? 15)
            ->appends($request->query());

        return $this->success(RoomResource::collection($rooms));
    }

    public function store(StoreRoomRequest $request): JsonResponse
    {
        $room = DB::transaction(fn () => Room::create($request->validated()));

        return $this->success(new RoomResource($room), 'Room created', 201);
    }

    public function update(UpdateRoomRequest $request, Room $room): JsonResponse
    {
        DB::transaction(fn () => $room->update($request->validated()));

        return $this->success(new RoomResource($room->refresh()), 'Room updated');
    }

    public function destroy(Room $room): JsonResponse
    {
        DB::transaction(fn () => $room->delete());

        return $this->success(null, 'Room deleted');
    }
}
