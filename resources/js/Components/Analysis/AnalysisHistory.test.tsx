import { describe, it, expect, vi } from 'vitest'
import { renderComponent, screen } from '@/test/utils'
import AnalysisHistory from './AnalysisHistory'
import type { AnalysisRun } from '@/types'

vi.mock('@inertiajs/react', () => ({
    Link: ({
        children,
        ...props
    }: {
        children: React.ReactNode
        [key: string]: unknown
    }) => <a {...props}>{children}</a>,
}))

const mockAnalyses: AnalysisRun[] = [
    {
        id: 1,
        period_start: '2026-01-01',
        period_end: '2026-01-31',
        prompt_used: 'Analyze spending',
        llm_response: 'Response 1',
        model: 'llama-3.3-70b',
        created_at: '2026-02-01',
        updated_at: '2026-02-01',
    },
    {
        id: 2,
        period_start: '2026-02-01',
        period_end: '2026-02-28',
        prompt_used: 'Analyze spending',
        llm_response: 'Response 2',
        model: 'gpt-4o',
        created_at: '2026-03-01',
        updated_at: '2026-03-01',
    },
]

describe('AnalysisHistory', () => {
    it('renders empty state when no analyses exist', () => {
        renderComponent(<AnalysisHistory analyses={[]} />)

        expect(screen.getByText(/No analyses yet/)).toBeInTheDocument()
    })

    it('renders a list of analysis links', () => {
        renderComponent(<AnalysisHistory analyses={mockAnalyses} />)

        expect(screen.getByText('2026-01-01 to 2026-01-31')).toBeInTheDocument()
        expect(screen.getByText('2026-02-01 to 2026-02-28')).toBeInTheDocument()
    })

    it('displays the model name for each analysis', () => {
        renderComponent(<AnalysisHistory analyses={mockAnalyses} />)

        expect(screen.getByText('llama-3.3-70b')).toBeInTheDocument()
        expect(screen.getByText('gpt-4o')).toBeInTheDocument()
    })

    it('links to the correct analysis detail page', () => {
        renderComponent(<AnalysisHistory analyses={mockAnalyses} />)

        const links = screen.getAllByRole('link')
        expect(links[0]).toHaveAttribute('href', '/analysis/1')
        expect(links[1]).toHaveAttribute('href', '/analysis/2')
    })

    it('displays the created_at date', () => {
        renderComponent(<AnalysisHistory analyses={mockAnalyses} />)

        expect(screen.getByText('2026-02-01')).toBeInTheDocument()
        expect(screen.getByText('2026-03-01')).toBeInTheDocument()
    })
})
