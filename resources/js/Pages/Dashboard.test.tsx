import { describe, it, expect, vi } from 'vitest'
import { renderComponent, screen } from '@/test/utils'
import Dashboard from './Dashboard'

vi.mock('@inertiajs/react', () => ({
    Link: ({
        children,
        ...props
    }: {
        children: React.ReactNode
        [key: string]: unknown
    }) => <a {...props}>{children}</a>,
    Head: ({ title }: { title: string }) => <title>{title}</title>,
    usePage: () => ({ url: '/' }),
}))

vi.mock('recharts', async () => {
    const actual = await vi.importActual('recharts')
    return {
        ...actual,
        ResponsiveContainer: ({ children }: { children: React.ReactNode }) => (
            <div>{children}</div>
        ),
    }
})

const defaultProps = {
    spendingByCategory: [
        { category: 'Groceries', total: 450.5 },
        { category: 'Transport', total: 120.0 },
    ],
    monthlyTrends: [{ month: '2026-01', income: 5000, expenses: 3200 }],
    recentTransactions: [
        {
            id: 1,
            date: '2026-01-15',
            description: 'Countdown',
            amount: -85.5,
            category: 'Groceries',
            account: 'Westpac',
            raw_text: 'COUNTDOWN',
            created_at: '2026-01-15',
            updated_at: '2026-01-15',
        },
    ],
}

describe('Dashboard', () => {
    it('renders the dashboard heading', () => {
        renderComponent(<Dashboard {...defaultProps} />)

        expect(
            screen.getByRole('heading', { name: 'Dashboard' }),
        ).toBeInTheDocument()
    })

    it('renders the spending chart section', () => {
        renderComponent(<Dashboard {...defaultProps} />)

        expect(
            screen.getByText('Monthly Spending by Category'),
        ).toBeInTheDocument()
    })

    it('renders the income vs expenses section', () => {
        renderComponent(<Dashboard {...defaultProps} />)

        expect(screen.getByText('Income vs Expenses')).toBeInTheDocument()
    })

    it('renders the recent transactions section', () => {
        renderComponent(<Dashboard {...defaultProps} />)

        expect(screen.getByText('Recent Transactions')).toBeInTheDocument()
    })

    it('renders transaction data', () => {
        renderComponent(<Dashboard {...defaultProps} />)

        expect(screen.getByText('Countdown')).toBeInTheDocument()
    })

    it('renders with empty data', () => {
        renderComponent(
            <Dashboard
                spendingByCategory={[]}
                monthlyTrends={[]}
                recentTransactions={[]}
            />,
        )

        expect(
            screen.getByText('No spending data for this month'),
        ).toBeInTheDocument()
        expect(screen.getByText('No transaction data yet')).toBeInTheDocument()
    })
})
