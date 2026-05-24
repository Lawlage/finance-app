import { Head, useForm } from '@inertiajs/react'
import AppLayout from '@/Components/Layout/AppLayout'
import AnalysisHistory from '@/Components/Analysis/AnalysisHistory'
import type { AnalysisRun } from '@/types'
import type { SyntheticEvent } from 'react'

interface AnalysisProps {
    analyses: AnalysisRun[]
}

export default function Analysis({ analyses }: AnalysisProps) {
    const { data, setData, post, processing, recentlySuccessful } = useForm({
        period_start: '',
        period_end: '',
    })

    function submit(e: SyntheticEvent) {
        e.preventDefault()
        post('/analysis')
    }

    return (
        <AppLayout>
            <Head title="Analysis" />
            <h1 className="mb-6 text-2xl font-bold text-gray-900">
                Spending Analysis
            </h1>

            <div className="mb-8 rounded-lg border border-gray-200 bg-white p-6">
                <h2 className="mb-4 text-lg font-semibold text-gray-900">
                    Run New Analysis
                </h2>
                <form onSubmit={submit} className="flex items-end gap-4">
                    <div>
                        <label
                            htmlFor="period_start"
                            className="block text-sm font-medium text-gray-700"
                        >
                            Start Date
                        </label>
                        <input
                            id="period_start"
                            type="date"
                            value={data.period_start}
                            onChange={(e) => {
                                setData('period_start', e.target.value)
                            }}
                            className="mt-1 rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:outline-none"
                            required
                        />
                    </div>
                    <div>
                        <label
                            htmlFor="period_end"
                            className="block text-sm font-medium text-gray-700"
                        >
                            End Date
                        </label>
                        <input
                            id="period_end"
                            type="date"
                            value={data.period_end}
                            onChange={(e) => {
                                setData('period_end', e.target.value)
                            }}
                            className="mt-1 rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:outline-none"
                            required
                        />
                    </div>
                    <button
                        type="submit"
                        disabled={processing}
                        className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none disabled:opacity-50"
                    >
                        {processing ? 'Dispatching...' : 'Run Analysis'}
                    </button>
                </form>
                {recentlySuccessful && (
                    <p className="mt-3 text-sm text-green-600">
                        Analysis job dispatched. Refresh to see results.
                    </p>
                )}
            </div>

            <div className="rounded-lg border border-gray-200 bg-white">
                <div className="border-b border-gray-200 px-6 py-4">
                    <h2 className="text-lg font-semibold text-gray-900">
                        Analysis History
                    </h2>
                </div>
                <AnalysisHistory analyses={analyses} />
            </div>
        </AppLayout>
    )
}
