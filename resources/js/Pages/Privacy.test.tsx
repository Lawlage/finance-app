import { describe, it, expect, vi, beforeEach } from 'vitest'
import { renderComponent, screen, fireEvent } from '@/test/utils'
import Privacy from './Privacy'

const mockPost = vi.fn()
const mockSetData = vi.fn()
const mockReset = vi.fn()
const { mockPatch, mockDelete } = vi.hoisted(() => ({
    mockPatch: vi.fn(),
    mockDelete: vi.fn(),
}))

let mockFormState = {
    data: { value: '', label: '' },
    setData: mockSetData,
    post: mockPost,
    reset: mockReset,
    processing: false,
}

vi.mock('@inertiajs/react', () => ({
    Link: ({
        children,
        ...props
    }: {
        children: React.ReactNode
        [key: string]: unknown
    }) => <a {...props}>{children}</a>,
    Head: ({ title }: { title: string }) => <title>{title}</title>,
    usePage: () => ({ url: '/privacy' }),
    useForm: () => mockFormState,
    router: { patch: mockPatch, delete: mockDelete },
}))

const props = {
    rules: [{ id: 1, value: '38-9009-0123456-00', label: 'Joint Savings' }],
    fallbackMode: 'pseudonym',
    accountLabels: ['Checking', 'Savings'],
    auditLog: [
        {
            id: 7,
            primitive: 'resource',
            name: 'finance://transactions',
            payload: '{"count":1}',
            created_at: '2026-05-27 10:00:00',
        },
    ],
}

beforeEach(() => {
    mockFormState = {
        data: { value: '', label: '' },
        setData: mockSetData,
        post: mockPost,
        reset: mockReset,
        processing: false,
    }
    mockPost.mockClear()
    mockSetData.mockClear()
    mockPatch.mockClear()
    mockDelete.mockClear()
})

describe('Privacy', () => {
    it('renders the heading and MCP endpoint', () => {
        renderComponent(<Privacy {...props} />)

        expect(
            screen.getByRole('heading', { name: 'Privacy & MCP' }),
        ).toBeInTheDocument()
        expect(screen.getByText(/\/mcp\/finance/)).toBeInTheDocument()
    })

    it('reflects the current fallback mode', () => {
        renderComponent(<Privacy {...props} />)

        const pseudonym = screen.getByRole('radio', {
            name: /Stable pseudonyms/,
        })
        expect(pseudonym).toBeChecked()
    })

    it('switches fallback mode via router.patch', () => {
        renderComponent(<Privacy {...props} />)

        fireEvent.click(screen.getByRole('radio', { name: /Flat redaction/ }))

        expect(mockPatch).toHaveBeenCalledWith('/privacy/settings', {
            fallback_mode: 'redact',
        })
    })

    it('lists replacement rules and account labels', () => {
        renderComponent(<Privacy {...props} />)

        expect(screen.getByText('38-9009-0123456-00')).toBeInTheDocument()
        expect(screen.getByText('Joint Savings')).toBeInTheDocument()
        expect(screen.getByText('Checking')).toBeInTheDocument()
    })

    it('submits a new replacement rule', () => {
        renderComponent(<Privacy {...props} />)

        fireEvent.submit(
            screen
                .getByRole('button', { name: 'Add' })
                .closest('form') as HTMLElement,
        )

        expect(mockPost).toHaveBeenCalled()
    })

    it('edits a replacement rule via router.patch', () => {
        renderComponent(<Privacy {...props} />)

        fireEvent.click(screen.getByRole('button', { name: 'Edit' }))
        expect(
            screen.getByDisplayValue('38-9009-0123456-00'),
        ).toBeInTheDocument()
        fireEvent.click(screen.getByRole('button', { name: 'Save' }))

        expect(mockPatch).toHaveBeenCalled()
    })

    it('expands an audit log entry to reveal the payload', () => {
        renderComponent(<Privacy {...props} />)

        expect(screen.queryByText('{"count":1}')).not.toBeInTheDocument()
        fireEvent.click(screen.getByText('finance://transactions'))
        expect(screen.getByText('{"count":1}')).toBeInTheDocument()
    })
})
