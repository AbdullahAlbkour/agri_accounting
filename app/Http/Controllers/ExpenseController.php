<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Season;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index()
    {
        $expenses = Expense::with(['season.crop', 'category'])->latest()->paginate(15);
        return view('expenses.index', compact('expenses'));
    }

    public function create()
    {
        $seasons = Season::where('status', 'active')->with('crop')->get();
        $categories = ExpenseCategory::all();
        return view('expenses.create', compact('seasons', 'categories'));
    }

    public function store(Request $request)
    {
        // تنظيف وتحويل الأرقام العربية إلى أرقام إنجليزية
        $amount = str_replace(['٠','١','٢','٣','٤','٥','٦','٧','٨','٩', ','], ['0','1','2','3','4','5','6','7','8','9', ''], (string) $request->amount);
        $exchange_rate = str_replace(['٠','١','٢','٣','٤','٥','٦','٧','٨','٩', ','], ['0','1','2','3','4','5','6','7','8','9', ''], (string) $request->exchange_rate);

        $request->merge([
            'amount' => $amount,
            'exchange_rate' => $exchange_rate ?: 1,
        ]);

        $request->validate([
            'season_id' => 'required|exists:seasons,id',
            'category_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|in:USD,TRY,SYP',
            'exchange_rate' => 'nullable|numeric|min:0.0001',
            'date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        // إيجاد التصنيف أو إنشاؤه تلقائياً
        $category = ExpenseCategory::firstOrCreate([
            'name' => trim($request->category_name)
        ]);

        Expense::create([
            'season_id' => $request->season_id,
            'expense_category_id' => $category->id,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'exchange_rate' => $request->exchange_rate ?: 1,
            'date' => $request->date,
            'notes' => $request->notes,
        ]);

        return redirect()->route('expenses.index')->with('success', 'تم تسجيل المصروف بنجاح.');
    }

    public function edit(Expense $expense)
    {
        $seasons = Season::all();
        $categories = ExpenseCategory::all();
        return view('expenses.edit', compact('expense', 'seasons', 'categories'));
    }

    public function update(Request $request, Expense $expense)
    {
        // تنظيف وتحويل الأرقام العربية إلى أرقام إنجليزية
        $amount = str_replace(['٠','١','٢','٣','٤','٥','٦','٧','٨','٩', ','], ['0','1','2','3','4','5','6','7','8','9', ''], (string) $request->amount);
        $exchange_rate = str_replace(['٠','١','٢','٣','٤','٥','٦','٧','٨','٩', ','], ['0','1','2','3','4','5','6','7','8','9', ''], (string) $request->exchange_rate);

        $request->merge([
            'amount' => $amount,
            'exchange_rate' => $exchange_rate ?: 1,
        ]);

        $request->validate([
            'season_id' => 'required|exists:seasons,id',
            'category_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|in:USD,TRY,SYP',
            'exchange_rate' => 'nullable|numeric|min:0.0001',
            'date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $category = ExpenseCategory::firstOrCreate([
            'name' => trim($request->category_name)
        ]);

        $expense->update([
            'season_id' => $request->season_id,
            'expense_category_id' => $category->id,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'exchange_rate' => $request->exchange_rate ?: 1,
            'date' => $request->date,
            'notes' => $request->notes,
        ]);

        return redirect()->route('expenses.index')->with('success', 'تم تحديث بيانات المصروف بنجاح.');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();
        return redirect()->route('expenses.index')->with('success', 'تم حذف المصروف بنجاح.');
    }
}