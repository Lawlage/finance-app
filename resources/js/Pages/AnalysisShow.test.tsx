import { describe, it, expect, vi, beforeEach } from 'vitest'
import { renderComponent, screen, fireEvent } from '@/test/utils'
import AnalysisShow from './AnalysisShow'

const { mockDelete } = vi.hoisted(() => ({ mockDelete: vi.fn() }))

vi.mock('@inertiajs/react', () => ({
    Link: ({
        children,
        ...props
    }: {
        children: React.ReactNode
        [key: string]: unknown
    }) => <a {...props}>{children}</a>,
    Head: ({ title }: { title: string }) => <title>{title}</title>,
    usePage: () => ({ url: '/analysis/1' }),
    router: { delete: mockDelete },
}))

beforeEach(() => {
    mockDelete.mockClear()
})

const mockAnalysis = {
    id: 1,
    period_start: '2026-01-01',
    period_end: '2026-01-31',
    prompt_used: 'Analyze spending',
    llm_response: 'You should reduce coffee spending.',
    model: 'llama-3.3-70b',
    created_at: '2026-02-01',
    updated_at: '2026-02-01',
}

describe('AnalysisShow', () => {
    it('renders the back link', () => {
        renderComponent(<AnalysisShow analysis={mockAnalysis} />)

        const backLink = screen.getByText(/Back to Analysis/)
        expect(backLink).toBeInTheDocument()
        expect(backLink).toHaveAttribute('href', '/analysis')
    })

    it('renders the recommendation card with analysis data', () => {
        renderComponent(<AnalysisShow analysis={mockAnalysis} />)

        expect(
            screen.getByText('You should reduce coffee spending.'),
        ).toBeInTheDocument()
        expect(screen.getByText('Model: llama-3.3-70b')).toBeInTheDocument()
        expect(screen.getByText(/Period:/)).toBeInTheDocument()
        expect(screen.getByText(/Jan 2026/)).toBeInTheDocument()
    })

    it('deletes the analysis via router.delete', () => {
        renderComponent(<AnalysisShow analysis={mockAnalysis} />)

        fireEvent.click(screen.getByRole('button', { name: 'Delete' }))

        expect(mockDelete).toHaveBeenCalledWith('/analysis/1')
    })
})
