const NZT = 'Pacific/Auckland'

export function formatDate(dateStr: string): string {
    const date = new Date(dateStr)
    return date.toLocaleDateString('en-NZ', {
        timeZone: NZT,
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    })
}

export function formatDateTime(dateStr: string): string {
    const date = new Date(dateStr)
    return date.toLocaleString('en-NZ', {
        timeZone: NZT,
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    })
}
