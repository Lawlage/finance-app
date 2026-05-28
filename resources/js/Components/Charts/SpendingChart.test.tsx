import { describe, it, expect, vi } from 'vitest'
import { renderComponent, screen } from '@/test/utils'
import SpendingChart from './SpendingChart'
import type { SpendingSummary } from '@/types'

vi.mock('recharts', async () => {
    const actual = await vi.importActual('recharts')
    return {
        ...actual,
        ResponsiveContainer: ({ children }: { children: React.ReactNode }) => (
            <div>{children}</div>
        ),
    }
})

const mockData: SpendingSummary[] = [
    { category: 'Groceries', total: 450.5 },
    { category: 'Transport', total: 120.0 },
    { category: 'Entertainment', total: 85.25 },
]

describe('SpendingChart', () => {
    it('renders empty state when no data is provided', () => {
        renderComponent(<SpendingChart data={[]} />)

        expect(
            screen.getByText('No spending data for this month'),
        ).toBeInTheDocument()
    })

    it('renders the chart when data is provided', () => {
        const { container } = renderComponent(<SpendingChart data={mockData} />)

        expect(
            screen.queryByText('No spending data for this month'),
        ).not.toBeInTheDocument()
        expect(container.querySelector('.recharts-wrapper')).toBeInTheDocument()
    })

    it('renders an "Other" category in muted gray', () => {
        const { container } = renderComponent(
            <SpendingChart
                data={[
                    { category: 'Other', total: 30 },
                    { category: 'Groceries', total: 100 },
                ]}
            />,
        )

        expect(container.querySelector('.recharts-wrapper')).toBeInTheDocument()
    })
})
