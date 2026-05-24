import { describe, it, expect, vi } from 'vitest'
import { renderComponent, screen } from '@/test/utils'
import AnalysisShow from './AnalysisShow'

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
}))

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
})
