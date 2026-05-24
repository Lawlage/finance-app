import { Head } from '@inertiajs/react'
import AppLayout from '@/Components/Layout/AppLayout'
import SpendingChart from '@/Components/Charts/SpendingChart'
import IncomeExpenseChart from '@/Components/Charts/IncomeExpenseChart'
import TransactionTable from '@/Components/Transactions/TransactionTable'
import type { SpendingSummary, MonthlyTrend, Transaction } from '@/types'

interface DashboardProps {
    spendingByCategory: SpendingSummary[]
    monthlyTrends: MonthlyTrend[]
    recentTransactions: Transaction[]
}

export default function Dashboard({
    spendingByCategory,
    monthlyTrends,
    recentTransactions,
}: DashboardProps) {
    return (
        <AppLayout>
            <Head title="Dashboard" />
            <h1 className="mb-6 text-2xl font-bold text-gray-900">Dashboard</h1>

            <div className="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div className="rounded-lg border border-gray-200 bg-white p-6">
                    <h2 className="mb-4 text-lg font-semibold text-gray-900">
                        Monthly Spending by Category
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
                        Recent Transactions
                    </h2>
                </div>
                <TransactionTable transactions={recentTransactions} />
            </div>
        </AppLayout>
    )
}
