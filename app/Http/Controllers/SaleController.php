<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GuardsClosedSeasons;
use App\Http\Controllers\Concerns\NormalizesNumbers;
use App\Models\Sale;
use App\Models\Season;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    use GuardsClosedSeasons, NormalizesNumbers;

    public function index(Request $request)
    {
        $query = Sale::with(['season.crop', 'payments']);

        if ($seasonId = $request->input('season_id')) {
            $query->where('season_id', $seasonId);
        }

        if ($buyer = $request->input('buyer_name')) {
            $query->where('buyer_name', $buyer);
        }

        $sales = $query->latest()->paginate(15)->withQueryString();
        $seasons = Season::with('crop')->orderBy('name')->get();

        return view('sales.index', compact('sales', 'seasons'));
    }

    public function create()
    {
        // المواسم المغلقة لا تظهر: لا يمكن تسجيل مبيعات عليها
        $seasons = Season::active()->with('crop')->get();

        return view('sales.create', compact('seasons'));
    }

    public function store(Request $request)
    {
        $request->merge([
            'tons' => $this->normalizeNumber($request->input('tons')),
            'extra_kg' => $this->normalizeNumber($request->input('extra_kg')),
            'unit_price' => $this->normalizeNumber($request->input('unit_price')),
            'paid_amount' => $this->normalizeNumber($request->input('paid_amount')) ?: 0,
            'exchange_rate' => $this->normalizeNumber($request->input('exchange_rate')) ?: 1,
        ]);

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

        if ($message = $this->closedSeasonMessageById($request->season_id)) {
            return back()->withInput()->with('error', $message);
        }

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
            'notes' => ($extra_kg > 0 ? "الوزن: {$tons} طن و {$extra_kg} كغ. " : '').$request->notes,
        ]);

        return redirect()->route('sales.index')->with('success', 'تم تسجيل عملية البيع بنجاح.');
    }

    public function edit(Sale $sale)
    {
        if ($message = $this->closedSeasonMessage($sale->season)) {
            return redirect()->route('sales.index')->with('error', $message);
        }

        $seasons = Season::active()->with('crop')->get();

        // استخراج الأطنان والكيلوات من الوزن الكلي
        $tons = floor($sale->quantity);
        $extra_kg = round(($sale->quantity - $tons) * 1000, 2);

        return view('sales.edit', compact('sale', 'seasons', 'tons', 'extra_kg'));
    }

    public function update(Request $request, Sale $sale)
    {
        if ($message = $this->closedSeasonMessage($sale->season)) {
            return redirect()->route('sales.index')->with('error', $message);
        }

        $request->merge([
            'tons' => $this->normalizeNumber($request->input('tons')),
            'extra_kg' => $this->normalizeNumber($request->input('extra_kg')),
            'unit_price' => $this->normalizeNumber($request->input('unit_price')),
            'paid_amount' => $this->normalizeNumber($request->input('paid_amount')) ?: 0,
            'exchange_rate' => $this->normalizeNumber($request->input('exchange_rate')) ?: 1,
        ]);

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

        // منع نقل الفاتورة إلى موسم مغلق
        if ($message = $this->closedSeasonMessageById($request->season_id)) {
            return back()->withInput()->with('error', $message);
        }

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
        if ($message = $this->closedSeasonMessage($sale->season)) {
            return back()->with('error', $message);
        }

        $sale->delete();

        return redirect()->route('sales.index')->with('success', 'تم حذف عملية البيع بنجاح.');
    }
}
