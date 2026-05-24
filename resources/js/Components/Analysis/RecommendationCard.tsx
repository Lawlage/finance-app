import Markdown from 'react-markdown'
import type { AnalysisRun } from '@/types'
import { formatDate } from '@/utils/date'

interface RecommendationCardProps {
    analysis: AnalysisRun
}

export default function RecommendationCard({
    analysis,
}: RecommendationCardProps) {
    return (
        <div className="rounded-lg border border-gray-200 bg-white p-6">
            <div className="mb-4 flex items-center justify-between">
                <h3 className="text-lg font-semibold text-gray-900">
                    Analysis Results
                </h3>
                <span className="text-xs text-gray-500">
                    Model: {analysis.model}
                </span>
            </div>
            <p className="mb-2 text-sm text-gray-500">
                Period: {formatDate(analysis.period_start)} to{' '}
                {formatDate(analysis.period_end)}
            </p>
            <div className="prose prose-sm max-w-none text-gray-700">
                <Markdown>{analysis.llm_response}</Markdown>
            </div>
        </div>
    )
}
