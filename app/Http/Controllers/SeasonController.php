<?php

namespace App\Http\Controllers;

use App\Models\Crop;
use App\Models\Field;
use App\Models\Season;
use Illuminate\Http\Request;

class SeasonController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status');

        $query = Season::with(['crop', 'field', 'expenses', 'sales']);

        if (in_array($status, ['active', 'closed'], true)) {
            $query->where('status', $status);
        }

        $seasons = $query->latest()->paginate(10)->withQueryString();

        return view('seasons.index', compact('seasons', 'status'));
    }

    /**
     * أرشيف المواسم المغلقة مع مقارنة إنتاجية المحاصيل والأرباح عبر السنوات.
     */
    public function archive(Request $request)
    {
        $cropId = $request->input('crop_id');
        $year = $request->input('year');

        $query = Season::with(['crop', 'field', 'expenses', 'sales'])->closed();

        if ($cropId) {
            $query->where('crop_id', $cropId);
        }

        if ($year) {
            $query->whereYear('start_date', $year);
        }

        $seasons = $query->orderByDesc('start_date')->get();

        // مقارنة الأداء عبر السنوات: (المحصول × السنة)
        $comparison = $seasons->groupBy(function (Season $season) {
            return ($season->crop->name ?? 'غير محدد').'|'.$season->year();
        })->map(function ($group, $key) {
            [$cropName, $year] = explode('|', $key);

            $sales = $group->sum(fn (Season $s) => $s->totalSalesUSD());
            $expenses = $group->sum(fn (Season $s) => $s->totalExpensesUSD());
            $quantity = $group->sum(fn (Season $s) => $s->totalQuantityTons());
            $area = $group->sum(fn (Season $s) => (float) ($s->field->area_dunums ?? 0));

            return [
                'crop' => $cropName,
                'year' => $year,
                'seasons_count' => $group->count(),
                'quantity_tons' => $quantity,
                'area_dunums' => $area,
                'yield_per_dunum' => $area > 0 ? $quantity / $area : null,
                'total_sales' => $sales,
                'total_expenses' => $expenses,
                'net_profit' => $sales - $expenses,
            ];
        })->sortBy([['crop', 'asc'], ['year', 'desc']])->values();

        // ملخّص لكل سنة (لمخطط المقارنة)
        $yearlyTotals = $comparison->groupBy('year')->map(function ($rows, $year) {
            return [
                'year' => $year,
                'total_sales' => $rows->sum('total_sales'),
                'total_expenses' => $rows->sum('total_expenses'),
                'net_profit' => $rows->sum('net_profit'),
                'quantity_tons' => $rows->sum('quantity_tons'),
            ];
        })->sortBy('year')->values();

        $crops = Crop::orderBy('name')->get();
        $years = Season::closed()->get()->map(fn (Season $s) => $s->year())->filter()->unique()->sortDesc()->values();

        return view('seasons.archive', compact('seasons', 'comparison', 'yearlyTotals', 'crops', 'years', 'cropId', 'year'));
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
        if ($season->isClosed()) {
            return redirect()->route('seasons.show', $season)
                ->with('error', 'هذا الموسم مغلق/مؤرشف. أعد فتحه أولاً لتتمكن من تعديل بياناته.');
        }

        $crops = Crop::all();
        $fields = Field::all();

        return view('seasons.edit', compact('season', 'crops', 'fields'));
    }

    public function update(Request $request, Season $season)
    {
        if ($season->isClosed()) {
            return redirect()->route('seasons.show', $season)
                ->with('error', 'لا يمكن تعديل موسم مغلق. أعد فتح الموسم أولاً.');
        }

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

    /**
     * إغلاق وأرشفة الموسم: تُجمّد مبيعاته ومصاريفه ضد أي تعديل.
     */
    public function close(Request $request, Season $season)
    {
        if ($season->isClosed()) {
            return back()->with('error', 'هذا الموسم مغلق بالفعل.');
        }

        $season->update([
            'status' => 'closed',
            'end_date' => $season->end_date ?: ($request->input('end_date') ?: now()->toDateString()),
        ]);

        return back()->with('success', 'تم إغلاق وأرشفة الموسم "'.$season->name.'". لم يعد بالإمكان تعديل مبيعاته أو مصاريفه.');
    }

    /**
     * إعادة فتح موسم مؤرشف للتعديل عليه.
     */
    public function reopen(Season $season)
    {
        if (! $season->isClosed()) {
            return back()->with('error', 'هذا الموسم مفتوح بالفعل.');
        }

        $season->update(['status' => 'active']);

        return back()->with('success', 'تمت إعادة فتح الموسم "'.$season->name.'" ويمكن الآن تعديل حركاته.');
    }

    public function destroy(Season $season)
    {
        if ($season->isClosed()) {
            return back()->with('error', 'لا يمكن حذف موسم مؤرشف. أعد فتحه أولاً إذا كنت متأكداً من الحذف.');
        }

        $season->delete();

        return redirect()->route('seasons.index')->with('success', 'تم حذف الموسم بنجاح.');
    }
}
