import { describe, it, expect, vi, beforeEach } from 'vitest'
import { renderComponent, screen, fireEvent } from '@/test/utils'
import Categories from './Categories'
import type { Category, CategoryRule } from '@/types'

const formPost = vi.fn()
const formReset = vi.fn()
const setData = vi.fn()
const { rPatch, rDelete, rPost } = vi.hoisted(() => ({
    rPatch: vi.fn(),
    rDelete: vi.fn(),
    rPost: vi.fn(),
}))

let sharedErrors: Record<string, string> = {}

vi.mock('@inertiajs/react', () => ({
    Link: ({
        children,
        ...props
    }: {
        children: React.ReactNode
        [key: string]: unknown
    }) => <a {...props}>{children}</a>,
    Head: ({ title }: { title: string }) => <title>{title}</title>,
    usePage: () => ({ url: '/categories' }),
    useForm: (initial: Record<string, string>) => ({
        data: initial,
        setData,
        post: formPost,
        reset: formReset,
        processing: false,
        get errors() {
            return sharedErrors
        },
    }),
    router: { patch: rPatch, delete: rDelete, post: rPost },
}))

const categories: Category[] = [
    {
        id: 1,
        name: 'Groceries',
        created_at: '2026-01-01',
        updated_at: '2026-01-01',
    },
]

const rules: CategoryRule[] = [
    {
        id: 5,
        category: 'Groceries',
        pattern: 'Countdown',
        created_at: '2026-01-01',
        updated_at: '2026-01-01',
    },
]

interface Callbacks {
    onSuccess?: () => void
    onFinish?: () => void
}

beforeEach(() => {
    sharedErrors = {}
    formPost.mockReset()
    formReset.mockReset()
    setData.mockReset()
    rPatch.mockReset()
    rDelete.mockReset()
    rPost.mockReset()
    // Invoke the inertia callbacks so onSuccess/onFinish handlers run.
    formPost.mockImplementation((_url: string, opts?: Callbacks) => {
        opts?.onSuccess?.()
    })
    rPatch.mockImplementation(
        (_url: string, _data: unknown, opts?: Callbacks) => {
            opts?.onSuccess?.()
        },
    )
    rDelete.mockImplementation((_url: string, opts?: Callbacks) => {
        opts?.onFinish?.()
    })
    rPost.mockImplementation(
        (_url: string, _data: unknown, opts?: Callbacks) => {
            opts?.onFinish?.()
        },
    )
})

describe('Categories', () => {
    it('renders the heading', () => {
        renderComponent(<Categories categories={categories} rules={rules} />)

        expect(
            screen.getByRole('heading', { name: 'Categories & Rules' }),
        ).toBeInTheDocument()
    })

    it('disables Recategorize All when there are no rules', () => {
        renderComponent(<Categories categories={categories} rules={[]} />)

        expect(
            screen.getByRole('button', { name: 'Recategorize All' }),
        ).toBeDisabled()
    })

    it('triggers recategorize via router.post', () => {
        renderComponent(<Categories categories={categories} rules={rules} />)

        fireEvent.click(
            screen.getByRole('button', { name: 'Recategorize All' }),
        )

        expect(rPost).toHaveBeenCalledWith(
            '/categories/recategorize',
            {},
            expect.anything(),
        )
    })

    it('submits a new category', () => {
        renderComponent(<Categories categories={categories} rules={rules} />)

        fireEvent.change(screen.getByPlaceholderText('New category name'), {
            target: { value: 'Dining' },
        })
        expect(setData).toHaveBeenCalledWith('name', 'Dining')

        fireEvent.submit(
            screen
                .getByRole('button', { name: 'Add' })
                .closest('form') as HTMLElement,
        )
        expect(formPost).toHaveBeenCalledWith('/categories', expect.anything())
    })

    it('shows a category validation error', () => {
        sharedErrors = { name: 'Name is required' }
        renderComponent(<Categories categories={categories} rules={rules} />)

        expect(screen.getByText('Name is required')).toBeInTheDocument()
    })

    it('shows empty state when there are no categories', () => {
        renderComponent(<Categories categories={[]} rules={rules} />)

        expect(screen.getByText('No categories yet.')).toBeInTheDocument()
    })

    it('edits a category and saves via the check button', () => {
        renderComponent(<Categories categories={categories} rules={rules} />)

        fireEvent.click(screen.getByRole('button', { name: 'Groceries' }))
        const input = screen.getByDisplayValue('Groceries')
        fireEvent.change(input, { target: { value: 'Food' } })
        fireEvent.click(screen.getByText('✓'))

        expect(rPatch).toHaveBeenCalledWith(
            '/categories/1',
            { name: 'Food' },
            expect.anything(),
        )
    })

    it('saves a category edit on Enter and ignores empty values', () => {
        renderComponent(<Categories categories={categories} rules={rules} />)

        fireEvent.click(screen.getByRole('button', { name: 'Groceries' }))
        const input = screen.getByDisplayValue('Groceries')

        fireEvent.change(input, { target: { value: '   ' } })
        fireEvent.keyDown(input, { key: 'Enter' })
        expect(rPatch).not.toHaveBeenCalled()

        fireEvent.change(input, { target: { value: 'Food' } })
        fireEvent.keyDown(input, { key: 'Enter' })
        expect(rPatch).toHaveBeenCalled()
    })

    it('cancels a category edit on Escape', () => {
        renderComponent(<Categories categories={categories} rules={rules} />)

        fireEvent.click(screen.getByRole('button', { name: 'Groceries' }))
        const input = screen.getByDisplayValue('Groceries')
        fireEvent.keyDown(input, { key: 'Escape' })

        expect(screen.queryByDisplayValue('Groceries')).not.toBeInTheDocument()
    })

    it('deletes a category via router.delete', () => {
        renderComponent(<Categories categories={categories} rules={rules} />)

        fireEvent.click(
            screen.getByRole('button', { name: 'Delete Groceries' }),
        )

        expect(rDelete).toHaveBeenCalledWith('/categories/1', expect.anything())
    })

    it('submits a new rule', () => {
        renderComponent(<Categories categories={categories} rules={rules} />)

        fireEvent.submit(
            screen
                .getByRole('button', { name: 'Add Rule' })
                .closest('form') as HTMLElement,
        )

        expect(formPost).toHaveBeenCalledWith(
            '/category-rules',
            expect.anything(),
        )
    })

    it('shows a rule validation error', () => {
        sharedErrors = { pattern: 'Pattern is required' }
        renderComponent(<Categories categories={categories} rules={rules} />)

        expect(screen.getByText('Pattern is required')).toBeInTheDocument()
    })

    it('shows empty state when there are no rules', () => {
        renderComponent(<Categories categories={categories} rules={[]} />)

        expect(screen.getByText('No rules yet.')).toBeInTheDocument()
    })

    it('edits a rule and saves it', () => {
        renderComponent(<Categories categories={categories} rules={rules} />)

        fireEvent.click(screen.getByTitle('Click to edit'))
        const patternInput = screen.getByDisplayValue('Countdown')
        fireEvent.change(patternInput, { target: { value: 'New World' } })
        fireEvent.click(screen.getByRole('button', { name: 'Save' }))

        expect(rPatch).toHaveBeenCalledWith(
            '/category-rules/5',
            { pattern: 'New World', category: 'Groceries' },
            expect.anything(),
        )
    })

    it('saves a rule edit on Enter and ignores empty values', () => {
        renderComponent(<Categories categories={categories} rules={rules} />)

        fireEvent.click(screen.getByTitle('Click to edit'))
        const categoryInput = screen.getAllByDisplayValue('Groceries')[0]

        fireEvent.change(categoryInput, { target: { value: '  ' } })
        fireEvent.keyDown(categoryInput, { key: 'Enter' })
        expect(rPatch).not.toHaveBeenCalled()

        fireEvent.change(categoryInput, { target: { value: 'Food' } })
        fireEvent.keyDown(categoryInput, { key: 'Enter' })
        expect(rPatch).toHaveBeenCalled()
    })

    it('cancels a rule edit on Escape and via Cancel', () => {
        renderComponent(<Categories categories={categories} rules={rules} />)

        fireEvent.click(screen.getByTitle('Click to edit'))
        fireEvent.keyDown(screen.getByDisplayValue('Countdown'), {
            key: 'Escape',
        })
        expect(screen.queryByDisplayValue('Countdown')).not.toBeInTheDocument()

        fireEvent.click(screen.getByTitle('Click to edit'))
        fireEvent.click(screen.getByRole('button', { name: 'Cancel' }))
        expect(screen.queryByDisplayValue('Countdown')).not.toBeInTheDocument()
    })

    it('deletes a rule via router.delete', () => {
        renderComponent(<Categories categories={categories} rules={rules} />)

        fireEvent.click(screen.getByRole('button', { name: 'Delete' }))

        expect(rDelete).toHaveBeenCalledWith(
            '/category-rules/5',
            expect.anything(),
        )
    })
})
