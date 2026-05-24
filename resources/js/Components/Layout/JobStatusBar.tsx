import { useEffect, useRef, useState } from 'react'
import type { JobStatus } from '../../types'

export default function JobStatusBar() {
    const [jobs, setJobs] = useState<JobStatus[]>([])
    const [dismissed, setDismissed] = useState<Set<number>>(new Set())
    const intervalRef = useRef<ReturnType<typeof setInterval> | null>(null)
    const hasPending = useRef(false)

    useEffect(() => {
        const poll = async () => {
            try {
                const res = await fetch('/api/job-statuses', {
                    credentials: 'same-origin',
                })
                if (!res.ok) return
                const data = (await res.json()) as JobStatus[]
                setJobs(data)

                const pending = data.some((j) => j.status === 'pending')
                if (pending && !hasPending.current) {
                    hasPending.current = true
                    if (intervalRef.current) clearInterval(intervalRef.current)
                    intervalRef.current = setInterval(() => void poll(), 2000)
                } else if (!pending && hasPending.current) {
                    hasPending.current = false
                    if (intervalRef.current) clearInterval(intervalRef.current)
                    intervalRef.current = setInterval(() => void poll(), 10000)
                }
            } catch {
                // silently ignore network errors
            }
        }

        void poll()
        intervalRef.current = setInterval(() => void poll(), 10000)
        return () => {
            if (intervalRef.current) clearInterval(intervalRef.current)
        }
    }, [])

    const dismiss = async (id: number) => {
        setDismissed((prev) => new Set(prev).add(id))
        try {
            await fetch(`/api/job-statuses/${String(id)}`, {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: { 'X-XSRF-TOKEN': getCsrfToken() },
            })
        } catch {
            // ignore
        }
    }

    const visible = jobs.filter((j) => !dismissed.has(j.id))
    if (visible.length === 0) return null

    return (
        <div className="fixed right-4 bottom-4 z-50 flex w-80 flex-col gap-2">
            {visible.map((job) => (
                <div
                    key={job.id}
                    className={`flex items-start gap-3 rounded-lg border p-3 shadow-lg ${statusStyles(job.status)}`}
                >
                    <div className="mt-0.5">{statusIcon(job.status)}</div>
                    <div className="min-w-0 flex-1">
                        <p className="text-sm font-medium">
                            {typeLabel(job.type)}
                        </p>
                        <p className="truncate text-xs opacity-80">
                            {job.message}
                        </p>
                    </div>
                    {job.status !== 'pending' && (
                        <button
                            onClick={() => void dismiss(job.id)}
                            className="shrink-0 text-sm opacity-60 hover:opacity-100"
                        >
                            ✕
                        </button>
                    )}
                </div>
            ))}
        </div>
    )
}

function getCsrfToken(): string {
    const match = document.cookie
        .split('; ')
        .find((c) => c.startsWith('XSRF-TOKEN='))
    return match ? decodeURIComponent(match.split('=')[1] ?? '') : ''
}

function typeLabel(type: string): string {
    const labels: Record<string, string> = {
        import: 'Import',
        categorize: 'Categorization',
        analysis: 'Analysis',
    }
    return labels[type] ?? type
}

function statusStyles(status: string): string {
    switch (status) {
        case 'pending':
            return 'border-blue-200 bg-blue-50 text-blue-900'
        case 'completed':
            return 'border-green-200 bg-green-50 text-green-900'
        case 'failed':
            return 'border-red-200 bg-red-50 text-red-900'
        default:
            return 'border-gray-200 bg-white text-gray-900'
    }
}

function statusIcon(status: string): string {
    switch (status) {
        case 'pending':
            return '⏳'
        case 'completed':
            return '✓'
        case 'failed':
            return '✗'
        default:
            return '•'
    }
}
