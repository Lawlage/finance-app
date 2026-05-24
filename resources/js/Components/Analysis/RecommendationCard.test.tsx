import { describe, it, expect } from 'vitest'
import { renderComponent, screen } from '@/test/utils'
import RecommendationCard from './RecommendationCard'

const mockAnalysis = {
    id: 1,
    period_start: '2026-01-01',
    period_end: '2026-01-31',
    prompt_used: 'Analyze spending',
    llm_response: 'You spent too much on coffee.',
    model: 'llama-3.3-70b',
    created_at: '2026-02-01',
}

describe('RecommendationCard', () => {
    it('renders the analysis response', () => {
        renderComponent(<RecommendationCard analysis={mockAnalysis} />)

        expect(
            screen.getByText('You spent too much on coffee.'),
        ).toBeInTheDocument()
    })

    it('shows the model name', () => {
        renderComponent(<RecommendationCard analysis={mockAnalysis} />)

        expect(screen.getByText('Model: llama-3.3-70b')).toBeInTheDocument()
    })

    it('displays the analysis period', () => {
        renderComponent(<RecommendationCard analysis={mockAnalysis} />)

        expect(
            screen.getByText('Period: 2026-01-01 to 2026-01-31'),
        ).toBeInTheDocument()
    })
})
