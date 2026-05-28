import { describe, it, expect, vi, beforeEach } from 'vitest'
import { renderComponent, screen, fireEvent } from '@/test/utils'
import Upload from './Upload'

const mockPost = vi.fn()
const mockSetData = vi.fn()
const { mockDelete } = vi.hoisted(() => ({ mockDelete: vi.fn() }))
let mockFormState = {
    data: { statement: null as File | null, account: '' },
    setData: mockSetData,
    post: mockPost,
    processing: false,
    errors: {} as Record<string, string>,
    recentlySuccessful: false,
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
    usePage: () => ({ url: '/upload' }),
    useForm: () => mockFormState,
    router: { delete: mockDelete },
}))

beforeEach(() => {
    mockFormState = {
        data: { statement: null, account: '' },
        setData: mockSetData,
        post: mockPost,
        processing: false,
        errors: {},
        recentlySuccessful: false,
    }
    mockPost.mockReset()
    mockSetData.mockReset()
    mockDelete.mockReset()
    mockDelete.mockImplementation(
        (_url: string, opts?: { onFinish?: () => void }) => {
            opts?.onFinish?.()
        },
    )
})

const mockImports = [
    {
        id: 1,
        filename: 'westpac-jan.csv',
        account: 'Westpac Everyday',
        transaction_count: 42,
        created_at: '2026-01-31T10:00:00Z',
        updated_at: '2026-01-31T10:00:00Z',
    },
]

describe('Upload', () => {
    it('renders the page heading', () => {
        renderComponent(<Upload />)

        expect(
            screen.getByRole('heading', { name: 'Upload Bank Statement' }),
        ).toBeInTheDocument()
    })

    it('renders the account name input', () => {
        renderComponent(<Upload />)

        expect(screen.getByLabelText('Account Name')).toBeInTheDocument()
    })

    it('renders the file drop zone', () => {
        renderComponent(<Upload />)

        expect(
            screen.getByText(
                'Drag and drop a bank statement, or click to browse',
            ),
        ).toBeInTheDocument()
    })

    it('renders the upload button as disabled when no file is selected', () => {
        renderComponent(<Upload />)

        const button = screen.getByRole('button', {
            name: 'Upload & Process',
        })
        expect(button).toBeDisabled()
    })

    it('calls setData when account name changes', () => {
        renderComponent(<Upload />)

        fireEvent.change(screen.getByLabelText('Account Name'), {
            target: { value: 'Westpac Everyday' },
        })

        expect(mockSetData).toHaveBeenCalledWith('account', 'Westpac Everyday')
    })

    it('renders the statement file label', () => {
        renderComponent(<Upload />)

        expect(screen.getByText('Statement File')).toBeInTheDocument()
    })

    it('calls post on form submission when file is selected', () => {
        const file = new File(['data'], 'test.csv', { type: 'text/csv' })
        mockFormState.data.statement = file

        renderComponent(<Upload />)

        fireEvent.submit(
            screen
                .getByRole('button', { name: 'Upload & Process' })
                .closest('form') as HTMLElement,
        )

        expect(mockPost).toHaveBeenCalledWith('/upload', {
            forceFormData: true,
        })
    })

    it('does not call post when no file is selected', () => {
        renderComponent(<Upload />)

        fireEvent.submit(
            screen
                .getByRole('button', { name: 'Upload & Process' })
                .closest('form') as HTMLElement,
        )

        expect(mockPost).not.toHaveBeenCalled()
    })

    it('shows success message when recently successful', () => {
        mockFormState.recentlySuccessful = true

        renderComponent(<Upload />)

        expect(
            screen.getByText(/Statement uploaded successfully/),
        ).toBeInTheDocument()
    })

    it('shows error messages for account field', () => {
        mockFormState.errors = { account: 'The account field is required.' }

        renderComponent(<Upload />)

        expect(
            screen.getByText('The account field is required.'),
        ).toBeInTheDocument()
    })

    it('shows error messages for statement field', () => {
        mockFormState.errors = { statement: 'Please select a file.' }

        renderComponent(<Upload />)

        expect(screen.getByText('Please select a file.')).toBeInTheDocument()
    })

    it('shows Uploading text when processing', () => {
        mockFormState.processing = true

        renderComponent(<Upload />)

        expect(
            screen.getByRole('button', { name: 'Uploading...' }),
        ).toBeInTheDocument()
    })

    it('renders import history when imports are provided', () => {
        renderComponent(<Upload imports={mockImports} />)

        expect(screen.getByText('Import History')).toBeInTheDocument()
        expect(screen.getByText('westpac-jan.csv')).toBeInTheDocument()
        expect(
            screen.getByText(/Westpac Everyday.*42 transactions/),
        ).toBeInTheDocument()
    })

    it('does not render import history when there are no imports', () => {
        renderComponent(<Upload imports={[]} />)

        expect(screen.queryByText('Import History')).not.toBeInTheDocument()
    })

    it('deletes an import via router.delete', () => {
        renderComponent(<Upload imports={mockImports} />)

        fireEvent.click(screen.getByRole('button', { name: 'Delete' }))

        expect(mockDelete).toHaveBeenCalledWith('/imports/1', expect.anything())
    })

    it('stores the chosen statement file via the dropzone', () => {
        const { container } = renderComponent(<Upload />)

        const fileInput = container.querySelector(
            'input[type="file"]',
        ) as HTMLInputElement
        const file = new File(['data'], 'statement.csv', { type: 'text/csv' })
        fireEvent.change(fileInput, { target: { files: [file] } })

        expect(mockSetData).toHaveBeenCalledWith('statement', file)
    })
})
