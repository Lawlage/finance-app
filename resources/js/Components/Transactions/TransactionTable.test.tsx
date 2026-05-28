import { describe, it, expect, vi, beforeEach } from 'vitest'
import { renderComponent, screen, fireEvent } from '@/test/utils'
import TransactionTable from './TransactionTable'
import type { Transaction } from '@/types'

const { mockPatch } = vi.hoisted(() => ({ mockPatch: vi.fn() }))

vi.mock('@inertiajs/react', () => ({
    router: { patch: mockPatch },
}))

beforeEach(() => {
    mockPatch.mockReset()
    mockPatch.mockImplementation(
        (_url: string, _data: unknown, opts?: { onSuccess?: () => void }) => {
            opts?.onSuccess?.()
        },
    )
})

const mockTransactions: Transaction[] = [
    {
        id: 1,
        date: '2026-01-15',
        description: 'Countdown Supermarket',
        amount: -85.5,
        category: 'Groceries',
        category_locked: false,
        account: 'Westpac Everyday',
        raw_text: 'COUNTDOWN 1234',
        import_id: null,
        created_at: '2026-01-15',
        updated_at: '2026-01-15',
    },
    {
        id: 2,
        date: '2026-01-16',
        description: 'Salary Deposit',
        amount: 3500.0,
        category: null,
        category_locked: false,
        account: 'Westpac Everyday',
        raw_text: 'SALARY DEP',
        import_id: null,
        created_at: '2026-01-16',
        updated_at: '2026-01-16',
    },
]

describe('TransactionTable', () => {
    it('renders empty state when no transactions exist', () => {
        renderComponent(<TransactionTable transactions={[]} />)

        expect(
            screen.getByText(
                'No transactions yet. Upload a statement to get started.',
            ),
        ).toBeInTheDocument()
    })

    it('renders table headers', () => {
        renderComponent(<TransactionTable transactions={mockTransactions} />)

        expect(screen.getByText('Date')).toBeInTheDocument()
        expect(screen.getByText('Description')).toBeInTheDocument()
        expect(screen.getByText('Category')).toBeInTheDocument()
        expect(screen.getByText('Account')).toBeInTheDocument()
        expect(screen.getByText('Amount')).toBeInTheDocument()
    })

    it('renders transaction data', () => {
        renderComponent(<TransactionTable transactions={mockTransactions} />)

        expect(screen.getByText(/15 Jan 2026/)).toBeInTheDocument()
        expect(screen.getByText('Countdown Supermarket')).toBeInTheDocument()
        expect(screen.getByText('Groceries')).toBeInTheDocument()
    })

    it('displays Uncategorized for null categories', () => {
        renderComponent(<TransactionTable transactions={mockTransactions} />)

        expect(screen.getByText('Uncategorized')).toBeInTheDocument()
    })

    it('formats amounts correctly with absolute values', () => {
        renderComponent(<TransactionTable transactions={mockTransactions} />)

        expect(screen.getByText('$85.50')).toBeInTheDocument()
        expect(screen.getByText('$3500.00')).toBeInTheDocument()
    })

    it('applies red color to negative amounts', () => {
        renderComponent(<TransactionTable transactions={mockTransactions} />)

        const negativeAmount = screen.getByText('$85.50')
        expect(negativeAmount).toHaveClass('text-red-600')
    })

    it('applies green color to positive amounts', () => {
        renderComponent(<TransactionTable transactions={mockTransactions} />)

        const positiveAmount = screen.getByText('$3500.00')
        expect(positiveAmount).toHaveClass('text-green-600')
    })

    it('saves an edited category via the Save button', () => {
        renderComponent(<TransactionTable transactions={mockTransactions} />)

        fireEvent.click(screen.getByText('Groceries'))
        const input = screen.getByDisplayValue('Groceries')
        fireEvent.change(input, { target: { value: 'Food' } })
        fireEvent.click(screen.getByRole('button', { name: 'Save' }))

        expect(mockPatch).toHaveBeenCalledWith(
            '/transactions/1/category',
            { category: 'Food' },
            expect.anything(),
        )
    })

    it('saves on Enter and ignores empty input', () => {
        renderComponent(<TransactionTable transactions={mockTransactions} />)

        fireEvent.click(screen.getByText('Uncategorized'))
        const input = screen.getByDisplayValue('')

        fireEvent.keyDown(input, { key: 'Enter' })
        expect(mockPatch).not.toHaveBeenCalled()

        fireEvent.change(input, { target: { value: 'Income' } })
        fireEvent.keyDown(input, { key: 'Enter' })
        expect(mockPatch).toHaveBeenCalled()
    })

    it('cancels editing on Escape and via the Cancel button', () => {
        renderComponent(<TransactionTable transactions={mockTransactions} />)

        fireEvent.click(screen.getByText('Groceries'))
        fireEvent.keyDown(screen.getByDisplayValue('Groceries'), {
            key: 'Escape',
        })
        expect(screen.queryByDisplayValue('Groceries')).not.toBeInTheDocument()

        fireEvent.click(screen.getByText('Groceries'))
        fireEvent.click(screen.getByRole('button', { name: 'Cancel' }))
        expect(screen.queryByDisplayValue('Groceries')).not.toBeInTheDocument()
    })

    it('shows a lock icon for category-locked transactions', () => {
        renderComponent(
            <TransactionTable
                transactions={[
                    {
                        ...mockTransactions[0],
                        category: 'Groceries',
                        category_locked: true,
                    },
                ]}
            />,
        )

        expect(screen.getByTitle('Manually overridden')).toBeInTheDocument()
    })

    it('styles the "Other" category in muted gray', () => {
        renderComponent(
            <TransactionTable
                transactions={[{ ...mockTransactions[0], category: 'Other' }]}
            />,
        )

        expect(screen.getByText('Other')).toHaveClass('bg-gray-200')
    })
})
