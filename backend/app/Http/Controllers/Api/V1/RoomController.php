<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Http\Resources\RoomResource;
use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $query = Room::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        return RoomResource::collection($query->paginate(15));
    }

    public function store(StoreRoomRequest $request)
    {
        $validated = $request->validated();

        if (!$request->user()->school_id) {
            $request->validate(['school_id' => 'required|exists:schools,id']);
            $validated['school_id'] = $request->input('school_id');
        } else {
            $validated['school_id'] = $request->user()->school_id;
        }

        $room = Room::create($validated);

        return (new RoomResource($room))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Room $room)
    {
        return new RoomResource($room);
    }

    public function update(UpdateRoomRequest $request, Room $room)
    {
        $room->update($request->validated());

        return new RoomResource($room);
    }

    public function destroy(Room $room)
    {
        if ($room->classes()->exists()) {
            return response()->json([
                'message' => 'Não é possível eliminar uma sala associada a turmas.'
            ], 422);
        }

        $room->delete();

        return response()->json(['message' => 'Sala eliminada com sucesso.']);
    }
}
