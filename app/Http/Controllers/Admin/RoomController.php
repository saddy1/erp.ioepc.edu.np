<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Room;

class RoomController extends Controller
{
    /** Ensure this checks admin role */
    private function ensureSuperAdmin(Request $request)
    {
        $admin = $request->attributes->get('admin');

        if (!$admin || !$admin->is_super_admin) {
            abort(403, 'Only super admin can manage rooms.');
        }
    }

    public function index(Request $request)
    {
        // VIEWING rooms is fine for everyone (optional — remove if you want!)
        $rooms = Room::orderBy('room_no')->paginate(20);
        return view('Backend.admin.rooms.index', compact('rooms'));
    }

    public function create(Request $request)
    {
        $this->ensureSuperAdmin($request);

        return view('Backend.admin.rooms.create');
    }

    public function store(Request $r)
    {
        $this->ensureSuperAdmin($r);

        $data = $r->validate([
            'room_no'            => ['required', 'string', 'max:50', 'unique:rooms,room_no'],
          
        ]);


        Room::create($data);

        return redirect()->route('rooms.index')->with('ok', 'Room added.');
    }

    public function edit(Request $request, Room $room)
    {
        $this->ensureSuperAdmin($request);

        return view('Backend.admin.rooms.edit', compact('room'));
    }

    public function update(Request $r, Room $room)
    {
        $this->ensureSuperAdmin($r);

        $data = $r->validate([
            'room_no'            => ['required', 'string', 'max:50', "unique:rooms,room_no,{$room->id}"],
           
        ]);

    
        $room->update($data);

        return redirect()->route('rooms.index')->with('ok', 'Room updated.');
    }

    public function destroy(Request $request, Room $room)
    {
        $this->ensureSuperAdmin($request);

        $room->delete();

        return back()->with('ok', 'Room deleted.');
    }
}
