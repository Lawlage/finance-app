import { useState } from 'react'
import { Head, router } from '@inertiajs/react'
import AppLayout from '@/Components/Layout/AppLayout'
import SpendingChart from '@/Components/Charts/SpendingChart'
import IncomeExpenseChart from '@/Components/Charts/IncomeExpenseChart'
import TransactionTable from '@/Components/Transactions/TransactionTable'
import type {
    SpendingSummary,
    MonthlyTrend,
    Transaction,
    PaginatedData,
} from '@/types'

interface Filters {
    range: string
    from: string
    to: string
}

export interface DashboardProps {
    spendingByCategory: SpendingSummary[]
    monthlyTrends: MonthlyTrend[]
    recentTransactions: PaginatedData<Transaction>
    currentPeriod?: string
    filters?: Filters
}

const PRESETS = [
    { value: 'this_month', label: 'This Month' },
    { value: 'last_month', label: 'Last Month' },
    { value: 'last_3_months', label: 'Last 3 Months' },
    { value: 'last_6_months', label: 'Last 6 Months' },
    { value: 'this_year', label: 'This Year' },
    { value: 'all_time', label: 'All Time' },
    { value: 'custom', label: 'Custom' },
]

export default function Dashboard({
    spendingByCategory,
    monthlyTrends,
    recentTransactions,
    currentPeriod,
    filters,
}: DashboardProps) {
    const activeRange = filters?.range ?? 'this_month'
    const [customFrom, setCustomFrom] = useState(filters?.from ?? '')
    const [customTo, setCustomTo] = useState(filters?.to ?? '')

    function applyRange(range: string) {
        if (range === 'custom') {
            router.get(
                '/',
                { range, from: customFrom, to: customTo },
                { preserveState: true },
            )
        } else {
            router.get('/', { range }, { preserveState: true })
        }
    }

    return (
        <AppLayout>
            <Head title="Dashboard" />
            <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
                <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>
                {currentPeriod && (
                    <span className="text-sm text-gray-500">
                        {currentPeriod}
                    </span>
                )}
            </div>

            <div className="mb-6 flex flex-wrap items-end gap-2">
                {PRESETS.map((preset) => (
                    <button
                        key={preset.value}
                        type="button"
                        onClick={() => {
                            if (preset.value !== 'custom') {
                                applyRange(preset.value)
                            } else {
                                applyRange('custom')
                            }
                        }}
                        className={`rounded-md px-3 py-1.5 text-sm font-medium ${
                            activeRange === preset.value
                                ? 'bg-indigo-600 text-white'
                                : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
                        }`}
                    >
                        {preset.label}
                    </button>
                ))}
                {activeRange === 'custom' && (
                    <div className="flex items-end gap-2">
                        <input
                            type="date"
                            value={customFrom}
                            onChange={(e) => {
                                setCustomFrom(e.target.value)
                            }}
                            className="rounded-md border border-gray-300 px-2 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:outline-none"
                        />
                        <span className="text-sm text-gray-400">to</span>
                        <input
                            type="date"
                            value={customTo}
                            onChange={(e) => {
                                setCustomTo(e.target.value)
                            }}
                            className="rounded-md border border-gray-300 px-2 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:outline-none"
                        />
                        <button
                            type="button"
                            onClick={() => {
                                applyRange('custom')
                            }}
                            className="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700"
                        >
                            Apply
                        </button>
                    </div>
                )}
            </div>

            <div className="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div className="rounded-lg border border-gray-200 bg-white p-6">
                    <h2 className="mb-4 text-lg font-semibold text-gray-900">
                        Spending by Category
                    </h2>
                    <SpendingChart data={spendingByCategory} />
                </div>

                <div className="rounded-lg border border-gray-200 bg-white p-6">
                    <h2 className="mb-4 text-lg font-semibold text-gray-900">
                        Income vs Expenses
                    </h2>
                    <IncomeExpenseChart data={monthlyTrends} />
                </div>
            </div>

            <div className="rounded-lg border border-gray-200 bg-white">
                <div className="border-b border-gray-200 px-6 py-4">
                    <h2 className="text-lg font-semibold text-gray-900">
                        Transactions
                        <span className="ml-2 text-sm font-normal text-gray-500">
                            {recentTransactions.total} total
                        </span>
                    </h2>
                </div>
                <TransactionTable transactions={recentTransactions.data} />
                {recentTransactions.last_page > 1 && (
                    <div className="flex items-center justify-between border-t border-gray-200 px-6 py-3">
                        <p className="text-sm text-gray-500">
                            Page {recentTransactions.current_page} of{' '}
                            {recentTransactions.last_page}
                        </p>
                        <div className="flex gap-2">
                            <button
                                type="button"
                                disabled={recentTransactions.current_page <= 1}
                                onClick={() => {
                                    router.get(
                                        '/',
                                        {
                                            ...filters,
                                            page:
                                                recentTransactions.current_page -
                                                1,
                                        },
                                        { preserveState: true },
                                    )
                                }}
                                className="rounded-md border border-gray-300 bg-white px-3 py-1 text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                            >
                                Previous
                            </button>
                            <button
                                type="button"
                                disabled={
                                    recentTransactions.current_page >=
                                    recentTransactions.last_page
                                }
                                onClick={() => {
                                    router.get(
                                        '/',
                                        {
                                            ...filters,
                                            page:
                                                recentTransactions.current_page +
                                                1,
                                        },
                                        { preserveState: true },
                                    )
                                }}
                                className="rounded-md border border-gray-300 bg-white px-3 py-1 text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                            >
                                Next
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    )
}
