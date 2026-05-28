import { describe, it, expect, vi, beforeEach } from 'vitest'
import { renderComponent, screen, fireEvent } from '@/test/utils'
import Dashboard from './Dashboard'

const { mockGet } = vi.hoisted(() => ({ mockGet: vi.fn() }))

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
    router: { get: mockGet },
}))

beforeEach(() => {
    mockGet.mockClear()
})

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
    recentTransactions: {
        data: [
            {
                id: 1,
                date: '2026-01-15',
                description: 'Countdown',
                amount: -85.5,
                category: 'Groceries',
                category_locked: false,
                account: 'Westpac',
                raw_text: 'COUNTDOWN',
                import_id: null,
                created_at: '2026-01-15',
                updated_at: '2026-01-15',
            },
        ],
        current_page: 1,
        last_page: 1,
        per_page: 100,
        total: 1,
    },
    categories: ['Groceries', 'Transport'],
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

        expect(screen.getByText('Spending by Category')).toBeInTheDocument()
    })

    it('renders the income vs expenses section', () => {
        renderComponent(<Dashboard {...defaultProps} />)

        expect(screen.getByText('Income vs Expenses')).toBeInTheDocument()
    })

    it('renders the transactions section', () => {
        renderComponent(<Dashboard {...defaultProps} />)

        expect(screen.getByText('Transactions')).toBeInTheDocument()
    })

    it('renders transaction data', () => {
        renderComponent(<Dashboard {...defaultProps} />)

        expect(screen.getByText('Countdown')).toBeInTheDocument()
    })

    it('renders the category filter with options', () => {
        renderComponent(<Dashboard {...defaultProps} />)

        expect(
            screen.getByRole('option', { name: 'All categories' }),
        ).toBeInTheDocument()
        expect(
            screen.getByRole('option', { name: 'Uncategorized' }),
        ).toBeInTheDocument()
        expect(
            screen.getByRole('option', { name: 'Groceries' }),
        ).toBeInTheDocument()
    })

    it('renders with empty data', () => {
        renderComponent(
            <Dashboard
                spendingByCategory={[]}
                monthlyTrends={[]}
                recentTransactions={{
                    data: [],
                    current_page: 1,
                    last_page: 1,
                    per_page: 100,
                    total: 0,
                }}
                categories={[]}
            />,
        )

        expect(
            screen.getByText('No spending data for this month'),
        ).toBeInTheDocument()
        expect(screen.getByText('No transaction data yet')).toBeInTheDocument()
    })

    it('shows the current period when provided', () => {
        renderComponent(
            <Dashboard {...defaultProps} currentPeriod="January 2026" />,
        )

        expect(screen.getByText('January 2026')).toBeInTheDocument()
    })

    it('applies a preset range via router.get', () => {
        renderComponent(<Dashboard {...defaultProps} />)

        fireEvent.click(screen.getByRole('button', { name: 'Last Month' }))

        expect(mockGet).toHaveBeenCalledWith(
            '/',
            expect.objectContaining({ range: 'last_month' }),
            expect.objectContaining({ preserveState: true }),
        )
    })

    it('shows custom date inputs and applies a custom range', () => {
        renderComponent(
            <Dashboard
                {...defaultProps}
                filters={{ range: 'custom', from: '', to: '' }}
            />,
        )

        const dateInputs = document.querySelectorAll('input[type="date"]')
        expect(dateInputs).toHaveLength(2)
        fireEvent.change(dateInputs[0], { target: { value: '2026-01-01' } })
        fireEvent.change(dateInputs[1], { target: { value: '2026-01-31' } })
        fireEvent.click(screen.getByRole('button', { name: 'Apply' }))

        expect(mockGet).toHaveBeenCalledWith(
            '/',
            expect.objectContaining({
                range: 'custom',
                from: '2026-01-01',
                to: '2026-01-31',
            }),
            expect.anything(),
        )
    })

    it('changes the trend breakdown via router.get', () => {
        renderComponent(<Dashboard {...defaultProps} />)

        const select = screen.getByRole('combobox', { name: /Breakdown/i })
        fireEvent.change(select, { target: { value: 'week' } })

        expect(mockGet).toHaveBeenCalledWith(
            '/',
            expect.objectContaining({ trend: 'week' }),
            expect.anything(),
        )
    })

    it('filters by category via router.get', () => {
        renderComponent(<Dashboard {...defaultProps} />)

        const select = screen.getByRole('combobox', { name: /Category/i })
        fireEvent.change(select, { target: { value: 'Groceries' } })

        expect(mockGet).toHaveBeenCalledWith(
            '/',
            expect.objectContaining({ category: 'Groceries' }),
            expect.anything(),
        )
    })

    it('paginates with Previous and Next', () => {
        const paged = {
            ...defaultProps,
            recentTransactions: {
                ...defaultProps.recentTransactions,
                current_page: 2,
                last_page: 3,
            },
        }
        renderComponent(<Dashboard {...paged} />)

        expect(screen.getByText('Page 2 of 3')).toBeInTheDocument()

        fireEvent.click(screen.getByRole('button', { name: 'Previous' }))
        expect(mockGet).toHaveBeenCalledWith(
            '/',
            expect.objectContaining({ page: 1 }),
            expect.anything(),
        )

        fireEvent.click(screen.getByRole('button', { name: 'Next' }))
        expect(mockGet).toHaveBeenCalledWith(
            '/',
            expect.objectContaining({ page: 3 }),
            expect.anything(),
        )
    })
})
