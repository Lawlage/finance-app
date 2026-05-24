import { Link } from '@inertiajs/react'
import type { AnalysisRun } from '@/types'

interface AnalysisHistoryProps {
    analyses: AnalysisRun[]
}

export default function AnalysisHistory({ analyses }: AnalysisHistoryProps) {
    if (analyses.length === 0) {
        return (
            <p className="py-4 text-gray-500">
                No analyses yet. Select a date range and run your first
                analysis.
            </p>
        )
    }

    return (
        <div className="divide-y divide-gray-200">
            {analyses.map((analysis) => (
                <Link
                    key={analysis.id}
                    href={`/analysis/${String(analysis.id)}`}
                    className="block px-4 py-3 hover:bg-gray-50"
                >
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-sm font-medium text-gray-900">
                                {analysis.period_start} to {analysis.period_end}
                            </p>
                            <p className="text-xs text-gray-500">
                                {analysis.model}
                            </p>
                        </div>
                        <p className="text-xs text-gray-400">
                            {analysis.created_at}
                        </p>
                    </div>
                </Link>
            ))}
        </div>
    )
}
