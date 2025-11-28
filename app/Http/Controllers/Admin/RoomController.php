<?php
// app/Http/Controllers/Admin/RoomController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Room;


class RoomController extends Controller
{

    public function index()
    {
        $rooms = Room::orderBy('room_no')->paginate(20);
        return view('Backend.admin.rooms.index', compact('rooms'));
    }

    public function create()
    {
        return view('Backend.admin.rooms.create');
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'room_no'            => ['required', 'string', 'max:50', 'unique:rooms,room_no'],
            'rows_col1'          => ['required', 'integer', 'min:0'],
            'rows_col2'          => ['required', 'integer', 'min:0'],
            'rows_col3'          => ['required', 'integer', 'min:0'],
            'faculties_per_room' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        $data['total_benches'] = $data['rows_col1'] + $data['rows_col2'] + $data['rows_col3'];

        Room::create($data);

        return redirect()->route('rooms.index')->with('ok', 'Room added.');
    }

    public function edit(Room $room)
    {
        return view('Backend.admin.rooms.edit', compact('room'));
    }

    public function update(Request $r, Room $room)
    {
        $data = $r->validate([
            'room_no'            => ['required', 'string', 'max:50', "unique:rooms,room_no,{$room->id}"],
            'rows_col1'          => ['required', 'integer', 'min:0'],
            'rows_col2'          => ['required', 'integer', 'min:0'],
            'rows_col3'          => ['required', 'integer', 'min:0'],
            'faculties_per_room' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        $data['total_benches'] = $data['rows_col1'] + $data['rows_col2'] + $data['rows_col3'];

        $room->update($data);

        return redirect()->route('rooms.index')->with('ok', 'Room updated.');
    }

    public function destroy(Room $room)
    {
        $room->delete();
        return back()->with('ok', 'Room deleted.');
    }

    
}
