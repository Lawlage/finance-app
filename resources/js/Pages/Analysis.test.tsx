import { describe, it, expect, vi } from 'vitest'
import { renderComponent, screen } from '@/test/utils'
import Analysis from './Analysis'

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
}))

const mockAnalyses = [
    {
        id: 1,
        period_start: '2026-01-01',
        period_end: '2026-01-31',
        prompt_used: null,
        llm_response: 'Response',
        model: 'claude (mcp)',
        created_at: '2026-02-01',
        updated_at: '2026-02-01',
    },
]

describe('Analysis', () => {
    it('renders the page heading', () => {
        renderComponent(<Analysis analyses={mockAnalyses} />)

        expect(
            screen.getByRole('heading', { name: 'Spending Analysis' }),
        ).toBeInTheDocument()
    })

    it('explains analyses are run via the Claude client', () => {
        renderComponent(<Analysis analyses={mockAnalyses} />)

        expect(
            screen.getByText(/Run analysis with your Claude client/),
        ).toBeInTheDocument()
        expect(screen.getByText('analyze_spending')).toBeInTheDocument()
    })

    it('no longer renders a run-analysis form', () => {
        renderComponent(<Analysis analyses={mockAnalyses} />)

        expect(
            screen.queryByRole('button', { name: /Run Analysis/ }),
        ).not.toBeInTheDocument()
    })

    it('renders the analysis history section', () => {
        renderComponent(<Analysis analyses={mockAnalyses} />)

        expect(screen.getByText('Analysis History')).toBeInTheDocument()
        const link = screen.getByRole('link', { name: /Jan 2026/ })
        expect(link).toBeInTheDocument()
    })

    it('renders empty analysis history message', () => {
        renderComponent(<Analysis analyses={[]} />)

        expect(screen.getByText(/No analyses yet/)).toBeInTheDocument()
    })
})
