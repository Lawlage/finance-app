import type { AnalysisRun } from '@/types'

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
                Period: {analysis.period_start} to {analysis.period_end}
            </p>
            <div className="prose prose-sm max-w-none whitespace-pre-wrap text-gray-700">
                {analysis.llm_response}
            </div>
        </div>
    )
}
