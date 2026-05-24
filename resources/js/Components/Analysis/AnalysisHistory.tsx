import { Link, router } from '@inertiajs/react'
import { useState } from 'react'
import type { AnalysisRun } from '@/types'
import { formatDate, formatDateTime } from '@/utils/date'

interface AnalysisHistoryProps {
    analyses: AnalysisRun[]
}

export default function AnalysisHistory({ analyses }: AnalysisHistoryProps) {
    const [deletingId, setDeletingId] = useState<number | null>(null)

    function deleteAnalysis(e: React.MouseEvent, id: number) {
        e.preventDefault()
        e.stopPropagation()
        setDeletingId(id)
        router.delete(`/analysis/${String(id)}`, {
            onFinish: () => {
                setDeletingId(null)
            },
        })
    }

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
                <div
                    key={analysis.id}
                    className="flex items-center justify-between px-4 py-3 hover:bg-gray-50"
                >
                    <Link
                        href={`/analysis/${String(analysis.id)}`}
                        className="flex-1"
                    >
                        <div>
                            <p className="text-sm font-medium text-gray-900">
                                {formatDate(analysis.period_start)} to{' '}
                                {formatDate(analysis.period_end)}
                            </p>
                            <p className="text-xs text-gray-500">
                                {analysis.model} &middot;{' '}
                                {formatDateTime(analysis.created_at)}
                            </p>
                        </div>
                    </Link>
                    <button
                        type="button"
                        onClick={(e) => {
                            deleteAnalysis(e, analysis.id)
                        }}
                        disabled={deletingId === analysis.id}
                        className="ml-4 text-sm text-red-600 hover:text-red-800 disabled:opacity-50"
                    >
                        {deletingId === analysis.id ? 'Deleting...' : 'Delete'}
                    </button>
                </div>
            ))}
        </div>
    )
}
