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

interface ChartEntry extends SpendingSummary {
    fill: string
}

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

    const colored: ChartEntry[] = data.map((entry) => ({
        ...entry,
        fill: entry.category.toLowerCase() === 'other' ? '#9ca3af' : '#4f46e5',
    }))

    return (
        <ResponsiveContainer width="100%" height={300}>
            <BarChart data={colored}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="category" fontSize={12} />
                <YAxis fontSize={12} />
                <Tooltip
                    formatter={(value) => [
                        `$${parseFloat(String(value)).toFixed(2)}`,
                        'Total',
                    ]}
                />
                <Bar dataKey="total" radius={[4, 4, 0, 0]} />
            </BarChart>
        </ResponsiveContainer>
    )
}
