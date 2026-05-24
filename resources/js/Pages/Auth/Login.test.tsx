import { describe, it, expect, vi, beforeEach } from 'vitest'
import { renderComponent, screen, fireEvent } from '@/test/utils'
import Login from './Login'

const mockPost = vi.fn()
const mockSetData = vi.fn()
let mockFormState = {
    data: { email: '', password: '', remember: false },
    setData: mockSetData,
    post: mockPost,
    processing: false,
    errors: {} as Record<string, string>,
}

vi.mock('@inertiajs/react', () => ({
    Head: ({ title }: { title: string }) => <title>{title}</title>,
    useForm: () => mockFormState,
}))

beforeEach(() => {
    mockFormState = {
        data: { email: '', password: '', remember: false },
        setData: mockSetData,
        post: mockPost,
        processing: false,
        errors: {},
    }
    mockPost.mockClear()
    mockSetData.mockClear()
})

describe('Login', () => {
    it('renders the login form', () => {
        renderComponent(<Login />)

        expect(screen.getByLabelText('Email')).toBeInTheDocument()
        expect(screen.getByLabelText('Password')).toBeInTheDocument()
        expect(screen.getByLabelText('Remember me')).toBeInTheDocument()
        expect(
            screen.getByRole('button', { name: 'Sign in' }),
        ).toBeInTheDocument()
    })

    it('renders within the guest layout', () => {
        renderComponent(<Login />)

        expect(screen.getByText('Finance Analyzer')).toBeInTheDocument()
        expect(
            screen.getByText('Personal finance insights powered by AI'),
        ).toBeInTheDocument()
    })

    it('calls setData when email changes', () => {
        renderComponent(<Login />)

        fireEvent.change(screen.getByLabelText('Email'), {
            target: { value: 'test@example.com' },
        })

        expect(mockSetData).toHaveBeenCalledWith('email', 'test@example.com')
    })

    it('calls setData when password changes', () => {
        renderComponent(<Login />)

        fireEvent.change(screen.getByLabelText('Password'), {
            target: { value: 'secret123' },
        })

        expect(mockSetData).toHaveBeenCalledWith('password', 'secret123')
    })

    it('calls setData when remember checkbox is toggled', () => {
        renderComponent(<Login />)

        fireEvent.click(screen.getByLabelText('Remember me'))

        expect(mockSetData).toHaveBeenCalledWith('remember', true)
    })

    it('calls post on form submission', () => {
        renderComponent(<Login />)

        fireEvent.submit(
            screen
                .getByRole('button', { name: 'Sign in' })
                .closest('form') as HTMLElement,
        )

        expect(mockPost).toHaveBeenCalledWith('/login')
    })

    it('displays email error when present', () => {
        mockFormState.errors = { email: 'Invalid email address.' }

        renderComponent(<Login />)

        expect(screen.getByText('Invalid email address.')).toBeInTheDocument()
    })

    it('does not display email error when absent', () => {
        renderComponent(<Login />)

        expect(
            screen.queryByText('Invalid email address.'),
        ).not.toBeInTheDocument()
    })

    it('shows Signing in text when processing', () => {
        mockFormState.processing = true

        renderComponent(<Login />)

        expect(
            screen.getByRole('button', { name: 'Signing in...' }),
        ).toBeInTheDocument()
    })
})
