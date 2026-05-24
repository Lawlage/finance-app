import {
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    ResponsiveContainer,
} from 'recharts'
import type { SpendingSummary } from '@/types'

interface SpendingChartProps {
    data: SpendingSummary[]
}

export default function SpendingChart({ data }: SpendingChartProps) {
    if (data.length === 0) {
        return (
            <div className="flex h-64 items-center justify-center text-gray-500">
                No spending data for this month
            </div>
        )
    }

    return (
        <ResponsiveContainer width="100%" height={300}>
            <BarChart data={data}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="category" fontSize={12} />
                <YAxis fontSize={12} />
                <Tooltip
                    formatter={(value) => [
                        `$${Number(value).toFixed(2)}`,
                        'Total',
                    ]}
                />
                <Bar dataKey="total" fill="#4f46e5" radius={[4, 4, 0, 0]} />
            </BarChart>
        </ResponsiveContainer>
    )
}
