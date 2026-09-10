<?php

namespace App\Http\Controllers;

use App\Models\BuyerPayment;
use App\Models\Crop;
use App\Models\Expense;
use App\Models\Field;
use App\Models\Sale;
use App\Models\Season;
use App\Services\DatabaseBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class BackupController extends Controller
{
    public function __construct(private DatabaseBackupService $backup) {}

    /**
     * صفحة الإعدادات والنسخ الاحتياطي.
     */
    public function index()
    {
        $stats = [
            'المحاصيل' => Crop::count(),
            'الأراضي' => Field::count(),
            'المواسم' => Season::count(),
            'المصاريف' => Expense::count(),
            'المبيعات' => Sale::count(),
            'سندات القبض' => BuyerPayment::count(),
        ];

        $driver = $this->backup->driver();
        $databaseName = $this->backup->databaseName();
        $usesMysqldump = $this->backup->mysqldumpAvailable();

        return view('settings.index', compact('stats', 'driver', 'databaseName', 'usesMysqldump'));
    }

    /**
     * تنزيل نسخة احتياطية من قاعدة البيانات كملف .sql بضغطة زر.
     */
    public function download(): StreamedResponse|RedirectResponse
    {
        try {
            $sql = $this->backup->generate();
        } catch (Throwable $e) {
            Log::error('فشل إنشاء النسخة الاحتياطية: '.$e->getMessage());

            return redirect()->route('settings.index')
                ->with('error', 'تعذّر إنشاء النسخة الاحتياطية: '.$e->getMessage());
        }

        $fileName = $this->backup->fileName();

        return response()->streamDownload(function () use ($sql) {
            echo $sql;
        }, $fileName, [
            'Content-Type' => 'application/sql; charset=UTF-8',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }
}
