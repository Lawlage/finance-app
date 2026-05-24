<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $currentMonth = Carbon::now();

        $spendingByCategory = Transaction::where('date', '>=', $currentMonth->copy()->startOfMonth())
            ->where('date', '<=', $currentMonth->copy()->endOfMonth())
            ->where('amount', '<', 0)
            ->whereNotNull('category')
            ->selectRaw('category, SUM(ABS(amount)) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $monthlyTrends = Transaction::selectRaw("DATE_FORMAT(date, '%Y-%m') as month")
            ->selectRaw('SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as income')
            ->selectRaw('SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as expenses')
            ->where('date', '>=', $currentMonth->copy()->subMonths(11)->startOfMonth())
            ->groupByRaw("DATE_FORMAT(date, '%Y-%m')")
            ->orderBy('month')
            ->get();

        $recentTransactions = Transaction::orderByDesc('date')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return Inertia::render('Dashboard', [
            'spendingByCategory' => $spendingByCategory,
            'monthlyTrends' => $monthlyTrends,
            'recentTransactions' => $recentTransactions,
        ]);
    }
}
