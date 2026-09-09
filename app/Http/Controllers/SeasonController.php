<?php

namespace App\Http\Controllers;

use App\Models\Season;
use App\Models\Crop;
use App\Models\Field;
use Illuminate\Http\Request;

class SeasonController extends Controller
{
    public function index()
    {
        $seasons = Season::with(['crop', 'field', 'expenses', 'sales'])->latest()->paginate(10);
        return view('seasons.index', compact('seasons'));
    }

    public function create()
    {
        $crops = Crop::all();
        $fields = Field::all();
        return view('seasons.create', compact('crops', 'fields'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'crop_id' => 'required|exists:crops,id',
            'field_id' => 'required|exists:fields,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:active,closed',
            'notes' => 'nullable|string',
        ]);

        Season::create($request->all());

        return redirect()->route('seasons.index')->with('success', 'تم إنشاء الموسم الزراعي بنجاح.');
    }

    public function show(Season $season)
    {
        $season->load(['crop', 'field', 'expenses.category', 'sales']);
        return view('seasons.show', compact('season'));
    }

    public function edit(Season $season)
    {
        $crops = Crop::all();
        $fields = Field::all();
        return view('seasons.edit', compact('season', 'crops', 'fields'));
    }

    public function update(Request $request, Season $season)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'crop_id' => 'required|exists:crops,id',
            'field_id' => 'required|exists:fields,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:active,closed',
            'notes' => 'nullable|string',
        ]);

        $season->update($request->all());

        return redirect()->route('seasons.index')->with('success', 'تم تحديث بيانات الموسم بنجاح.');
    }

    public function destroy(Season $season)
    {
        $season->delete();
        return redirect()->route('seasons.index')->with('success', 'تم حذف الموسم بنجاح.');
    }
}