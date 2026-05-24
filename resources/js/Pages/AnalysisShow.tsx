import { Head, Link } from '@inertiajs/react'
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
            <div className="mb-6 flex items-center gap-4">
                <Link
                    href="/analysis"
                    className="text-sm text-indigo-600 hover:text-indigo-800"
                >
                    &larr; Back to Analysis
                </Link>
            </div>
            <RecommendationCard analysis={analysis} />
        </AppLayout>
    )
}
