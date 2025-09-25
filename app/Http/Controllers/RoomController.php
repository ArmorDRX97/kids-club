<?php
namespace App\Http\Controllers;
use App\Models\Room;
use Illuminate\Http\Request;


class RoomController extends Controller
{
    public function index(){ $rooms = Room::orderBy('name')->paginate(20); return view('rooms.index', compact('rooms')); }
    public function create(){ return view('rooms.create'); }
    public function store(Request $request){
        $data = $request->validate([
            'name'=>['required','string','max:150'],
            'number_label'=>['nullable','string','max:50'],
            'capacity'=>['nullable','integer','min:1'],
            'spec'=>['nullable','string']
        ]);
        Room::create($data); return redirect()->route('rooms.index')->with('success','Комната создана');
    }
    public function edit(Room $room){ return view('rooms.edit', compact('room')); }
    public function update(Request $request, Room $room){
        $data = $request->validate([
            'name'=>['required','string','max:150'],
            'number_label'=>['nullable','string','max:50'],
            'capacity'=>['nullable','integer','min:1'],
            'spec'=>['nullable','string']
        ]);
        $room->update($data); return redirect()->route('rooms.index')->with('success','Обновлено');
    }
    public function destroy(Room $room){ $room->delete(); return back()->with('success','Удалено'); }
}
