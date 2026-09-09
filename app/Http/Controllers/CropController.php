<?php

namespace App\Http\Controllers;

use App\Models\Crop;
use Illuminate\Http\Request;

class CropController extends Controller
{
    public function index()
    {
        $crops = Crop::withCount('seasons')->latest()->paginate(10);
        return view('crops.index', compact('crops'));
    }

    public function create()
    {
        return view('crops.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:crops,name',
        ]);

        Crop::create($request->only('name'));

        return redirect()->route('crops.index')->with('success', 'تمت إضافة المحصول بنجاح.');
    }

    public function edit(Crop $crop)
    {
        return view('crops.edit', compact('crop'));
    }

    public function update(Request $request, Crop $crop)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:crops,name,' . $crop->id,
        ]);

        $crop->update($request->only('name'));

        return redirect()->route('crops.index')->with('success', 'تم تحديث بيانات المحصول بنجاح.');
    }

    public function destroy(Crop $crop)
    {
        $crop->delete();
        return redirect()->route('crops.index')->with('success', 'تم حذف المحصول بنجاح.');
    }
}