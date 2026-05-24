import { describe, it, expect, vi, beforeEach } from 'vitest'
import { renderComponent, screen, fireEvent } from '@/test/utils'
import Analysis from './Analysis'

const mockPost = vi.fn()
const mockSetData = vi.fn()
let mockFormState = {
    data: { period_start: '', period_end: '' },
    setData: mockSetData,
    post: mockPost,
    processing: false,
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
    usePage: () => ({ url: '/analysis' }),
    useForm: () => mockFormState,
}))

const mockAnalyses = [
    {
        id: 1,
        period_start: '2026-01-01',
        period_end: '2026-01-31',
        prompt_used: 'Analyze',
        llm_response: 'Response',
        model: 'llama-3.3-70b',
        created_at: '2026-02-01',
        updated_at: '2026-02-01',
    },
]

beforeEach(() => {
    mockFormState = {
        data: { period_start: '', period_end: '' },
        setData: mockSetData,
        post: mockPost,
        processing: false,
        recentlySuccessful: false,
    }
    mockPost.mockClear()
    mockSetData.mockClear()
})

describe('Analysis', () => {
    it('renders the page heading', () => {
        renderComponent(<Analysis analyses={mockAnalyses} />)

        expect(
            screen.getByRole('heading', { name: 'Spending Analysis' }),
        ).toBeInTheDocument()
    })

    it('renders the run analysis form', () => {
        renderComponent(<Analysis analyses={mockAnalyses} />)

        expect(screen.getByText('Run New Analysis')).toBeInTheDocument()
        expect(screen.getByLabelText('Start Date')).toBeInTheDocument()
        expect(screen.getByLabelText('End Date')).toBeInTheDocument()
        expect(
            screen.getByRole('button', { name: 'Run Analysis' }),
        ).toBeInTheDocument()
    })

    it('renders the analysis history section', () => {
        renderComponent(<Analysis analyses={mockAnalyses} />)

        expect(screen.getByText('Analysis History')).toBeInTheDocument()
        expect(screen.getByText('2026-01-01 to 2026-01-31')).toBeInTheDocument()
    })

    it('calls setData when date inputs change', () => {
        renderComponent(<Analysis analyses={[]} />)

        fireEvent.change(screen.getByLabelText('Start Date'), {
            target: { value: '2026-03-01' },
        })

        expect(mockSetData).toHaveBeenCalledWith('period_start', '2026-03-01')
    })

    it('calls setData when end date changes', () => {
        renderComponent(<Analysis analyses={[]} />)

        fireEvent.change(screen.getByLabelText('End Date'), {
            target: { value: '2026-03-31' },
        })

        expect(mockSetData).toHaveBeenCalledWith('period_end', '2026-03-31')
    })

    it('calls post on form submission', () => {
        renderComponent(<Analysis analyses={[]} />)

        fireEvent.submit(
            screen
                .getByRole('button', { name: 'Run Analysis' })
                .closest('form') as HTMLElement,
        )

        expect(mockPost).toHaveBeenCalledWith('/analysis')
    })

    it('renders empty analysis history message', () => {
        renderComponent(<Analysis analyses={[]} />)

        expect(screen.getByText(/No analyses yet/)).toBeInTheDocument()
    })

    it('shows success message when recently successful', () => {
        mockFormState.recentlySuccessful = true

        renderComponent(<Analysis analyses={[]} />)

        expect(
            screen.getByText(
                'Analysis job dispatched. Refresh to see results.',
            ),
        ).toBeInTheDocument()
    })

    it('shows Dispatching text when processing', () => {
        mockFormState.processing = true

        renderComponent(<Analysis analyses={[]} />)

        expect(
            screen.getByRole('button', { name: 'Dispatching...' }),
        ).toBeInTheDocument()
    })
})
