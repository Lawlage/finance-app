<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $range = $request->query('range', 'this_month');
        $from = $request->query('from');
        $to = $request->query('to');

        $latestDate = Transaction::max('date');
        $referenceDate = $latestDate ? Carbon::parse($latestDate) : Carbon::now();

        [$startDate, $endDate, $periodLabel] = $this->resolveDateRange(
            (string) $range,
            $referenceDate,
            is_string($from) ? $from : null,
            is_string($to) ? $to : null,
        );

        $spendingByCategory = Transaction::where('date', '>=', $startDate)
            ->where('date', '<=', $endDate)
            ->where('amount', '<', 0)
            ->whereNotNull('category')
            ->selectRaw('category, SUM(ABS(amount)) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $monthlyTrends = Transaction::selectRaw("DATE_FORMAT(date, '%Y-%m') as month")
            ->selectRaw('SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as income')
            ->selectRaw('SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as expenses')
            ->where('date', '>=', $startDate)
            ->where('date', '<=', $endDate)
            ->groupByRaw("DATE_FORMAT(date, '%Y-%m')")
            ->orderBy('month')
            ->get();

        $transactions = Transaction::where('date', '>=', $startDate)
            ->where('date', '<=', $endDate)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Dashboard', [
            'spendingByCategory' => $spendingByCategory,
            'monthlyTrends' => $monthlyTrends,
            'recentTransactions' => $transactions,
            'currentPeriod' => $periodLabel,
            'filters' => [
                'range' => $range,
                'from' => $startDate->format('Y-m-d'),
                'to' => $endDate->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function resolveDateRange(string $range, Carbon $referenceDate, ?string $from, ?string $to): array
    {
        return match ($range) {
            'last_month' => [
                $referenceDate->copy()->subMonth()->startOfMonth(),
                $referenceDate->copy()->subMonth()->endOfMonth(),
                $referenceDate->copy()->subMonth()->format('F Y'),
            ],
            'last_3_months' => [
                $referenceDate->copy()->subMonths(2)->startOfMonth(),
                $referenceDate->copy()->endOfMonth(),
                $referenceDate->copy()->subMonths(2)->format('M Y').' – '.$referenceDate->format('M Y'),
            ],
            'last_6_months' => [
                $referenceDate->copy()->subMonths(5)->startOfMonth(),
                $referenceDate->copy()->endOfMonth(),
                $referenceDate->copy()->subMonths(5)->format('M Y').' – '.$referenceDate->format('M Y'),
            ],
            'this_year' => [
                $referenceDate->copy()->startOfYear(),
                $referenceDate->copy()->endOfYear(),
                $referenceDate->format('Y'),
            ],
            'all_time' => [
                Carbon::parse('2000-01-01'),
                Carbon::parse('2099-12-31'),
                'All Time',
            ],
            'custom' => [
                $from ? Carbon::parse($from)->startOfDay() : $referenceDate->copy()->startOfMonth(),
                $to ? Carbon::parse($to)->endOfDay() : $referenceDate->copy()->endOfMonth(),
                ($from ?? '?').' to '.($to ?? '?'),
            ],
            default => [
                $referenceDate->copy()->startOfMonth(),
                $referenceDate->copy()->endOfMonth(),
                $referenceDate->format('F Y'),
            ],
        };
    }
}
