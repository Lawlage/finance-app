import { describe, it, expect, vi, beforeEach } from 'vitest'
import { renderComponent, screen, fireEvent } from '@/test/utils'
import AnalysisHistory from './AnalysisHistory'
import type { AnalysisRun } from '@/types'

const { mockDelete } = vi.hoisted(() => ({ mockDelete: vi.fn() }))

vi.mock('@inertiajs/react', () => ({
    Link: ({
        children,
        ...props
    }: {
        children: React.ReactNode
        [key: string]: unknown
    }) => <a {...props}>{children}</a>,
    router: { delete: mockDelete },
}))

beforeEach(() => {
    mockDelete.mockReset()
    mockDelete.mockImplementation(
        (_url: string, opts?: { onFinish?: () => void }) => {
            opts?.onFinish?.()
        },
    )
})

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

        const links = screen.getAllByRole('link')
        expect(links).toHaveLength(2)
        expect(links[0]).toHaveTextContent(/Jan 2026/)
        expect(links[1]).toHaveTextContent(/Feb 2026/)
    })

    it('displays the model name for each analysis', () => {
        renderComponent(<AnalysisHistory analyses={mockAnalyses} />)

        expect(screen.getByText(/llama-3.3-70b/)).toBeInTheDocument()
        expect(screen.getByText(/gpt-4o/)).toBeInTheDocument()
    })

    it('links to the correct analysis detail page', () => {
        renderComponent(<AnalysisHistory analyses={mockAnalyses} />)

        const links = screen.getAllByRole('link')
        expect(links[0]).toHaveAttribute('href', '/analysis/1')
        expect(links[1]).toHaveAttribute('href', '/analysis/2')
    })

    it('displays the created_at date', () => {
        renderComponent(<AnalysisHistory analyses={mockAnalyses} />)

        expect(screen.getByText(/llama-3.3-70b/)).toHaveTextContent(/Feb 2026/)
        expect(screen.getByText(/gpt-4o/)).toHaveTextContent(/Mar 2026/)
    })

    it('deletes an analysis via router.delete', () => {
        renderComponent(<AnalysisHistory analyses={mockAnalyses} />)

        fireEvent.click(screen.getAllByRole('button', { name: 'Delete' })[0])

        expect(mockDelete).toHaveBeenCalledWith(
            '/analysis/1',
            expect.anything(),
        )
    })
})
