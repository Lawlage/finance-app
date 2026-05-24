export interface Transaction {
    id: number
    date: string
    description: string
    amount: number
    category: string | null
    category_locked: boolean
    account: string
    raw_text: string
    import_id: number | null
    created_at: string
    updated_at: string
}

export interface Category {
    id: number
    name: string
    created_at: string
    updated_at: string
}

export interface CategoryRule {
    id: number
    category: string
    pattern: string
    created_at: string
    updated_at: string
}

export interface Import {
    id: number
    filename: string
    account: string
    transaction_count: number
    created_at: string
    updated_at: string
}

export interface AnalysisRun {
    id: number
    period_start: string
    period_end: string
    prompt_used: string
    llm_response: string
    model: string
    created_at: string
    updated_at: string
}

export interface JobStatus {
    id: number
    type: string
    status: 'pending' | 'completed' | 'failed'
    message: string
    created_at: string
    updated_at: string
}

export interface User {
    id: number
    name: string
    email: string
    email_verified_at: string | null
}

export interface PaginatedData<T> {
    data: T[]
    current_page: number
    last_page: number
    per_page: number
    total: number
}

export interface SpendingSummary {
    category: string
    total: number
}

export interface MonthlyTrend {
    month: string
    income: number
    expenses: number
}
