import { describe, it, expect, vi } from 'vitest'
import { renderComponent, screen } from '@/test/utils'
import IncomeExpenseChart from './IncomeExpenseChart'
import type { MonthlyTrend } from '@/types'

vi.mock('recharts', async () => {
    const actual = await vi.importActual('recharts')
    return {
        ...actual,
        ResponsiveContainer: ({ children }: { children: React.ReactNode }) => (
            <div>{children}</div>
        ),
    }
})

const mockData: MonthlyTrend[] = [
    { month: '2026-01', income: 5000, expenses: 3200 },
    { month: '2026-02', income: 4800, expenses: 2900 },
]

describe('IncomeExpenseChart', () => {
    it('renders empty state when no data is provided', () => {
        renderComponent(<IncomeExpenseChart data={[]} />)

        expect(screen.getByText('No transaction data yet')).toBeInTheDocument()
    })

    it('renders the chart when data is provided', () => {
        const { container } = renderComponent(
            <IncomeExpenseChart data={mockData} />,
        )

        expect(
            screen.queryByText('No transaction data yet'),
        ).not.toBeInTheDocument()
        expect(container.querySelector('.recharts-wrapper')).toBeInTheDocument()
    })
})
