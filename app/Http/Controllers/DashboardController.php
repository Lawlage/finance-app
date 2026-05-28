<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /** Sentinel filter value selecting transactions with no category. */
    private const string UNCATEGORIZED = '__uncategorized__';

    public function index(Request $request): Response
    {
        $range = $request->query('range', 'this_month');
        $from = $request->query('from');
        $to = $request->query('to');

        /** @var string|null $latestDate */
        $latestDate = Transaction::max('date');
        $referenceDate = $latestDate !== null ? Carbon::parse($latestDate) : Carbon::now();

        [$startDate, $endDate, $periodLabel] = $this->resolveDateRange(
            (string) $range,
            $referenceDate,
            is_string($from) ? $from : null,
            is_string($to) ? $to : null,
        );

        $spendingByCategory = Transaction::excludingTransfers()
            ->where('date', '>=', $startDate)
            ->where('date', '<=', $endDate)
            ->where('amount', '<', 0)
            ->whereNotNull('category')
            ->selectRaw('category, SUM(ABS(amount)) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $trend = $request->query('trend', 'month');
        $trend = in_array($trend, ['day', 'week', 'month', 'period'], true) ? $trend : 'month';

        $incomeExpr = 'SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as income';
        $expenseExpr = 'SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as expenses';

        // Bucket label for the income-vs-expense trend at the requested granularity.
        $labelExpr = match ($trend) {
            'day' => "DATE_FORMAT(date, '%Y-%m-%d')",
            'week' => "DATE_FORMAT(date, '%x-W%v')",
            default => "DATE_FORMAT(date, '%Y-%m')",
        };

        $monthlyTrends = $trend === 'period'
            ? Transaction::excludingTransfers()
                ->selectRaw("'Entire period' as month")
                ->selectRaw($incomeExpr)
                ->selectRaw($expenseExpr)
                ->where('date', '>=', $startDate)
                ->where('date', '<=', $endDate)
                ->get()
            : Transaction::excludingTransfers()
                ->selectRaw($labelExpr.' as month')
                ->selectRaw($incomeExpr)
                ->selectRaw($expenseExpr)
                ->where('date', '>=', $startDate)
                ->where('date', '<=', $endDate)
                ->groupByRaw($labelExpr)
                ->orderByRaw($labelExpr)
                ->get();

        $category = $request->query('category');
        $category = is_string($category) && $category !== '' ? $category : null;

        $transactions = Transaction::where('date', '>=', $startDate)
            ->where('date', '<=', $endDate)
            ->when($category === self::UNCATEGORIZED, fn ($query) => $query->whereNull('category'))
            ->when(
                $category !== null && $category !== self::UNCATEGORIZED,
                fn ($query) => $query->where('category', $category),
            )
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(100)
            ->withQueryString();

        return Inertia::render('Dashboard', [
            'spendingByCategory' => $spendingByCategory,
            'monthlyTrends' => $monthlyTrends,
            'recentTransactions' => $transactions,
            'categories' => Category::orderBy('name')->pluck('name'),
            'currentPeriod' => $periodLabel,
            'filters' => [
                'range' => $range,
                'from' => $startDate->format('Y-m-d'),
                'to' => $endDate->format('Y-m-d'),
                'trend' => $trend,
                'category' => $category,
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
