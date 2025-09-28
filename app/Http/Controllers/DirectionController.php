<?php

namespace App\Http\Controllers;

use App\Models\Direction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DirectionController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:directions,name'],
        ]);

        Direction::create($data);

        return redirect()->route('sections.index')->with('success', 'Направление создано.');
    }

    public function update(Request $request, Direction $direction)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:directions,name,' . $direction->id],
        ]);

        $direction->update($data);

        return redirect()->route('sections.index')->with('success', 'Направление обновлено.');
    }

    public function destroy(Direction $direction)
    {
        DB::transaction(function () use ($direction) {
            $direction->sections()->update(['direction_id' => null]);
            $direction->delete();
        });

        return redirect()->route('sections.index')->with('success', 'Направление удалено.');
    }
}
