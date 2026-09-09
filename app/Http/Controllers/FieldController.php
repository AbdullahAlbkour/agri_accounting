<?php

namespace App\Http\Controllers;

use App\Models\Field;
use Illuminate\Http\Request;

class FieldController extends Controller
{
    public function index()
    {
        $fields = Field::withCount('seasons')->latest()->paginate(10);
        return view('fields.index', compact('fields'));
    }

    public function create()
    {
        return view('fields.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'area_dunums' => 'nullable|numeric|min:0',
            'ownership_type' => 'required|in:owned,rented,shared',
        ]);

        Field::create($request->all());

        return redirect()->route('fields.index')->with('success', 'تمت إضافة الأرض بنجاح.');
    }

    public function edit(Field $field)
    {
        return view('fields.edit', compact('field'));
    }

    public function update(Request $request, Field $field)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'area_dunums' => 'nullable|numeric|min:0',
            'ownership_type' => 'required|in:owned,rented,shared',
        ]);

        $field->update($request->all());

        return redirect()->route('fields.index')->with('success', 'تم تحديث بيانات الأرض بنجاح.');
    }

    public function destroy(Field $field)
    {
        $field->delete();
        return redirect()->route('fields.index')->with('success', 'تم حذف الأرض بنجاح.');
    }
}