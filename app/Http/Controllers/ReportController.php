<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request, ReportService $reportService)
    {
        $validated = $request->validate([
            'month' => 'nullable|date_format:Y-m',
        ]);

        $currentMonth = CarbonImmutable::now()->startOfMonth();
        $month = isset($validated['month'])
            ? CarbonImmutable::createFromFormat('!Y-m', $validated['month'])
            : $currentMonth;

        if ($month->greaterThan($currentMonth)) {
            $month = $currentMonth;
        }

        $report = $reportService->summary($this->currentBranchId(), $month->startOfMonth(), $month->endOfMonth());
        $months = collect(range(0, 11))->map(fn (int $offset) => $currentMonth->subMonths($offset));

        return view('reports.index', compact('report', 'month', 'months'));
    }
}
