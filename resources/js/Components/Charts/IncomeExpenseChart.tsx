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
                <Tooltip
                    formatter={(value: number) => `$${value.toFixed(2)}`}
                />
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
