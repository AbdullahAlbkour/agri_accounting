<?php

namespace App\Http\Controllers;

use App\Services\AlertService;

class AlertController extends Controller
{
    public function __construct(private AlertService $alerts) {}

    /**
     * صفحة التنبيهات والإشعارات الذكية.
     */
    public function index()
    {
        $alerts = $this->alerts->all();
        $thresholds = $this->alerts->thresholds();

        $groups = [
            'debt' => [
                'title' => 'تنبيهات ديون التجار المتأخرة',
                'icon' => 'fa-user-clock',
                'color' => 'danger',
                'items' => $alerts->where('type', 'debt')->values(),
            ],
            'expense_ratio' => [
                'title' => 'تنبيهات سقف المصاريف',
                'icon' => 'fa-money-bill-trend-up',
                'color' => 'warning',
                'items' => $alerts->where('type', 'expense_ratio')->values(),
            ],
            'season_idle' => [
                'title' => 'مواسم بلا إيرادات',
                'icon' => 'fa-hourglass-half',
                'color' => 'secondary',
                'items' => $alerts->where('type', 'season_idle')->values(),
            ],
        ];

        return view('alerts.index', compact('alerts', 'groups', 'thresholds'));
    }
}
