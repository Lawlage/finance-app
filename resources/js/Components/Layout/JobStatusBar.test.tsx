import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { render, screen, waitFor, fireEvent } from '@/test/utils'
import JobStatusBar from './JobStatusBar'
import type { JobStatus } from '@/types'

const fetchMock = vi.fn()

function job(overrides: Partial<JobStatus> = {}): JobStatus {
    return {
        id: 1,
        type: 'import',
        status: 'completed',
        message: 'Imported 10 transactions',
        created_at: '2026-05-27 10:00:00',
        updated_at: '2026-05-27 10:00:00',
        ...overrides,
    }
}

function jsonResponse(data: JobStatus[]) {
    return { ok: true, json: () => Promise.resolve(data) }
}

beforeEach(() => {
    fetchMock.mockReset()
    vi.stubGlobal('fetch', fetchMock)
})

afterEach(() => {
    vi.unstubAllGlobals()
})

describe('JobStatusBar', () => {
    it('renders nothing when there are no jobs', async () => {
        fetchMock.mockResolvedValue(jsonResponse([]))
        const { container } = render(<JobStatusBar />)

        await waitFor(() => {
            expect(fetchMock).toHaveBeenCalled()
        })
        expect(container).toBeEmptyDOMElement()
    })

    it('renders nothing when the request is not ok', async () => {
        fetchMock.mockResolvedValue({ ok: false })
        const { container } = render(<JobStatusBar />)

        await waitFor(() => {
            expect(fetchMock).toHaveBeenCalled()
        })
        expect(container).toBeEmptyDOMElement()
    })

    it('silently ignores network errors', async () => {
        fetchMock.mockRejectedValue(new Error('network down'))
        const { container } = render(<JobStatusBar />)

        await waitFor(() => {
            expect(fetchMock).toHaveBeenCalled()
        })
        expect(container).toBeEmptyDOMElement()
    })

    it('renders import, categorize, and analysis jobs with labels', async () => {
        fetchMock.mockResolvedValue(
            jsonResponse([
                job({ id: 1, type: 'import', status: 'completed' }),
                job({
                    id: 2,
                    type: 'categorize',
                    status: 'failed',
                    message: 'Gateway error',
                }),
                job({
                    id: 3,
                    type: 'analysis',
                    status: 'pending',
                    message: 'Working...',
                }),
                job({ id: 4, type: 'unknown', status: 'queued' as 'pending' }),
            ]),
        )
        render(<JobStatusBar />)

        expect(await screen.findByText('Import')).toBeInTheDocument()
        expect(screen.getByText('Categorization')).toBeInTheDocument()
        expect(screen.getByText('Analysis')).toBeInTheDocument()
        expect(screen.getByText('unknown')).toBeInTheDocument()
        expect(screen.getByText('Gateway error')).toBeInTheDocument()
    })

    it('does not show a dismiss button for pending jobs', async () => {
        fetchMock.mockResolvedValue(
            jsonResponse([job({ id: 3, status: 'pending' })]),
        )
        render(<JobStatusBar />)

        await screen.findByText('Import')
        expect(
            screen.queryByRole('button', { name: '✕' }),
        ).not.toBeInTheDocument()
    })

    it('dismisses a completed job and calls DELETE', async () => {
        fetchMock.mockResolvedValue(
            jsonResponse([job({ id: 9, status: 'completed' })]),
        )
        render(<JobStatusBar />)

        await screen.findByText('Import')
        fireEvent.click(screen.getByRole('button', { name: '✕' }))

        await waitFor(() => {
            expect(fetchMock).toHaveBeenCalledWith(
                '/api/job-statuses/9',
                expect.objectContaining({ method: 'DELETE' }),
            )
        })
        await waitFor(() => {
            expect(screen.queryByText('Import')).not.toBeInTheDocument()
        })
    })

    it('speeds up then slows down polling as pending state changes', async () => {
        vi.useFakeTimers()
        try {
            fetchMock
                .mockResolvedValueOnce(
                    jsonResponse([job({ id: 1, status: 'pending' })]),
                )
                .mockResolvedValueOnce(
                    jsonResponse([job({ id: 1, status: 'completed' })]),
                )
                .mockResolvedValue(jsonResponse([]))
            render(<JobStatusBar />)

            await vi.advanceTimersByTimeAsync(0)
            await vi.advanceTimersByTimeAsync(2000)

            expect(fetchMock.mock.calls.length).toBeGreaterThanOrEqual(2)
        } finally {
            vi.useRealTimers()
        }
    })
})
