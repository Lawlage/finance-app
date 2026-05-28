import {
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    Legend,
    ResponsiveContainer,
} from 'recharts'
import type { MonthlyTrend } from '@/types'

interface IncomeExpenseChartProps {
    data: MonthlyTrend[]
}

interface TooltipProps {
    active?: boolean
    payload?: { payload: MonthlyTrend }[]
}

function formatCurrency(value: number): string {
    return `$${value.toFixed(2)}`
}

export function IncomeExpenseTooltip({ active, payload }: TooltipProps) {
    if (!active || !payload || payload.length === 0) {
        return null
    }

    // Aggregated totals arrive from the API as numeric strings, so coerce.
    const { month } = payload[0].payload
    const income = Number(payload[0].payload.income)
    const expenses = Number(payload[0].payload.expenses)
    const difference = income - expenses
    const positive = difference >= 0

    return (
        <div className="rounded-md border border-gray-200 bg-white p-3 text-sm shadow-md">
            <p className="mb-1 font-medium text-gray-900">{month}</p>
            <p className="text-green-600">Income: {formatCurrency(income)}</p>
            <p className="text-red-600">Expenses: {formatCurrency(expenses)}</p>
            <p
                className={`mt-1 border-t border-gray-100 pt-1 font-medium ${
                    positive ? 'text-green-600' : 'text-red-600'
                }`}
            >
                Difference: {positive ? '+' : '−'}
                {formatCurrency(Math.abs(difference))}
            </p>
        </div>
    )
}

export default function IncomeExpenseChart({ data }: IncomeExpenseChartProps) {
    if (data.length === 0) {
        return (
            <div className="flex h-64 items-center justify-center text-gray-500">
                No transaction data yet
            </div>
        )
    }

    return (
        <ResponsiveContainer width="100%" height={300}>
            <BarChart data={data}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="month" fontSize={12} />
                <YAxis fontSize={12} />
                <Tooltip content={<IncomeExpenseTooltip />} />
                <Legend />
                <Bar
                    dataKey="income"
                    fill="#22c55e"
                    name="Income"
                    radius={[4, 4, 0, 0]}
                />
                <Bar
                    dataKey="expenses"
                    fill="#ef4444"
                    name="Expenses"
                    radius={[4, 4, 0, 0]}
                />
            </BarChart>
        </ResponsiveContainer>
    )
}
