import { Head, Link, router } from '@inertiajs/react'
import AppLayout from '@/Components/Layout/AppLayout'
import RecommendationCard from '@/Components/Analysis/RecommendationCard'
import type { AnalysisRun } from '@/types'

interface AnalysisShowProps {
    analysis: AnalysisRun
}

export default function AnalysisShow({ analysis }: AnalysisShowProps) {
    return (
        <AppLayout>
            <Head title="Analysis Details" />
            <div className="mb-6 flex items-center justify-between">
                <Link
                    href="/analysis"
                    className="text-sm text-indigo-600 hover:text-indigo-800"
                >
                    &larr; Back to Analysis
                </Link>
                <button
                    type="button"
                    onClick={() => {
                        router.delete(`/analysis/${String(analysis.id)}`)
                    }}
                    className="rounded-md border border-red-300 px-3 py-1 text-sm text-red-600 hover:bg-red-50"
                >
                    Delete
                </button>
            </div>
            <RecommendationCard analysis={analysis} />
        </AppLayout>
    )
}
