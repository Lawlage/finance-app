import { describe, it, expect, vi } from 'vitest'
import { renderComponent, screen } from '@/test/utils'
import IncomeExpenseChart, { IncomeExpenseTooltip } from './IncomeExpenseChart'
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

describe('IncomeExpenseTooltip', () => {
    it('renders nothing when inactive', () => {
        const { container } = renderComponent(
            <IncomeExpenseTooltip active={false} payload={[]} />,
        )

        expect(container).toBeEmptyDOMElement()
    })

    it('shows a positive difference in green with a + sign', () => {
        renderComponent(
            <IncomeExpenseTooltip
                active
                payload={[
                    {
                        payload: {
                            month: '2026-01',
                            income: 5000,
                            expenses: 3200,
                        },
                    },
                ]}
            />,
        )

        const diff = screen.getByText(/Difference:/)
        expect(diff).toHaveTextContent('+$1800.00')
        expect(diff).toHaveClass('text-green-600')
    })

    it('coerces numeric-string totals from the API', () => {
        renderComponent(
            <IncomeExpenseTooltip
                active
                payload={[
                    {
                        payload: {
                            month: '2026-03',
                            income: '5000.00',
                            expenses: '3200.00',
                        },
                    },
                ]}
            />,
        )

        expect(screen.getByText(/Difference:/)).toHaveTextContent('+$1800.00')
        expect(screen.getByText(/Income:/)).toHaveTextContent('$5000.00')
    })

    it('shows a negative difference in red with a − sign', () => {
        renderComponent(
            <IncomeExpenseTooltip
                active
                payload={[
                    {
                        payload: {
                            month: '2026-02',
                            income: 2000,
                            expenses: 3200,
                        },
                    },
                ]}
            />,
        )

        const diff = screen.getByText(/Difference:/)
        expect(diff).toHaveTextContent('−$1200.00')
        expect(diff).toHaveClass('text-red-600')
    })
})
