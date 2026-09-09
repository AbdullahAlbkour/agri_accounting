<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Season;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function index()
    {
        $sales = Sale::with('season.crop')->latest()->paginate(15);
        return view('sales.index', compact('sales'));
    }

    public function create()
    {
        $seasons = Season::where('status', 'active')->with('crop')->get();
        return view('sales.create', compact('seasons'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'season_id' => 'required|exists:seasons,id',
            'buyer_name' => 'required|string|max:255',
            'tons' => 'nullable|numeric|min:0',
            'extra_kg' => 'nullable|numeric|min:0',
            'unit_price' => 'required|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0',
            'currency' => 'required|in:USD,TRY,SYP',
            'exchange_rate' => 'nullable|numeric|min:0.0001',
            'date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $tons = $request->input('tons', 0) ?: 0;
        $extra_kg = $request->input('extra_kg', 0) ?: 0;
        
        $totalWeightInTons = $tons + ($extra_kg / 1000);
        $total_price = $totalWeightInTons * $request->unit_price;
        $remaining_amount = max(0, $total_price - $request->paid_amount);

        Sale::create([
            'season_id' => $request->season_id,
            'buyer_name' => $request->buyer_name,
            'quantity' => $totalWeightInTons,
            'unit' => 'طن',
            'unit_price' => $request->unit_price,
            'total_price' => $total_price,
            'paid_amount' => $request->paid_amount,
            'remaining_amount' => $remaining_amount,
            'currency' => $request->currency,
            'exchange_rate' => $request->exchange_rate ?: 1,
            'date' => $request->date,
            'notes' => ($extra_kg > 0 ? "الوزن: {$tons} طن و {$extra_kg} كغ. " : "") . $request->notes,
        ]);

        return redirect()->route('sales.index')->with('success', 'تم تسجيل عملية البيع بنجاح.');
    }

    public function edit(Sale $sale)
    {
        $seasons = Season::all();
        
        // استخراج الأطنان والكيلوات من الوزن الكلي
        $tons = floor($sale->quantity);
        $extra_kg = round(($sale->quantity - $tons) * 1000, 2);

        return view('sales.edit', compact('sale', 'seasons', 'tons', 'extra_kg'));
    }

    public function update(Request $request, Sale $sale)
    {
        $request->validate([
            'season_id' => 'required|exists:seasons,id',
            'buyer_name' => 'required|string|max:255',
            'tons' => 'nullable|numeric|min:0',
            'extra_kg' => 'nullable|numeric|min:0',
            'unit_price' => 'required|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0',
            'currency' => 'required|in:USD,TRY,SYP',
            'exchange_rate' => 'nullable|numeric|min:0.0001',
            'date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $tons = $request->input('tons', 0) ?: 0;
        $extra_kg = $request->input('extra_kg', 0) ?: 0;
        
        $totalWeightInTons = $tons + ($extra_kg / 1000);
        $total_price = $totalWeightInTons * $request->unit_price;
        $remaining_amount = max(0, $total_price - $request->paid_amount);

        $sale->update([
            'season_id' => $request->season_id,
            'buyer_name' => $request->buyer_name,
            'quantity' => $totalWeightInTons,
            'unit' => 'طن',
            'unit_price' => $request->unit_price,
            'total_price' => $total_price,
            'paid_amount' => $request->paid_amount,
            'remaining_amount' => $remaining_amount,
            'currency' => $request->currency,
            'exchange_rate' => $request->exchange_rate ?: 1,
            'date' => $request->date,
            'notes' => $request->notes,
        ]);

        return redirect()->route('sales.index')->with('success', 'تم تحديث بيانات الفاتورة بنجاح.');
    }

    public function destroy(Sale $sale)
    {
        $sale->delete();
        return redirect()->route('sales.index')->with('success', 'تم حذف عملية البيع بنجاح.');
    }
}