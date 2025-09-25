<?php
namespace App\Http\Controllers;
use App\Models\{Package, Section};
use Illuminate\Http\Request;


class PackageController extends Controller
{
    public function index()
    {
        $packages = Package::with('section')->orderBy('section_id')->paginate(30);
        $sections = Section::orderBy('name')->get();
        return view('packages.index', compact('packages','sections'));
    }


    public function create()
    {
        $sections = Section::orderBy('name')->get();
        return view('packages.create', compact('sections'));
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'section_id'=>['required','exists:sections,id'],
            'type'=>['required','in:visits,period'],
            'visits_count'=>['nullable','integer','min:1'],
            'days'=>['nullable','integer','min:1'],
            'price'=>['required','numeric','min:0']
        ]);
        if($data['type']==='visits'){ $data['days']=null; }
        else { $data['visits_count']=null; }
        Package::create($data);
        return redirect()->route('packages.index')->with('success','Пакет сохранён');
    }


    public function edit(Package $package)
    {
        $sections = Section::orderBy('name')->get();
        return view('packages.edit', compact('package','sections'));
    }


    public function update(Request $request, Package $package)
    {
        $data = $request->validate([
            'section_id'=>['required','exists:sections,id'],
            'type'=>['required','in:visits,period'],
            'visits_count'=>['nullable','integer','min:1'],
            'days'=>['nullable','integer','min:1'],
            'price'=>['required','numeric','min:0']
        ]);
        if($data['type']==='visits'){ $data['days']=null; }
        else { $data['visits_count']=null; }
        $package->update($data);
        return redirect()->route('packages.index')->with('success','Обновлено');
    }


    public function destroy(Package $package)
    {
        $package->delete();
        return back()->with('success','Удалено');
    }
}
