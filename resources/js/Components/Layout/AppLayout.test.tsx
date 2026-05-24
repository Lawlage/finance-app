import { describe, it, expect, vi } from 'vitest'
import { renderComponent, screen } from '@/test/utils'
import AppLayout from './AppLayout'

vi.mock('@inertiajs/react', () => ({
    Link: ({
        children,
        ...props
    }: {
        children: React.ReactNode
        [key: string]: unknown
    }) => <a {...props}>{children}</a>,
    usePage: () => ({ url: '/' }),
}))

describe('AppLayout', () => {
    it('renders the app name', () => {
        renderComponent(
            <AppLayout>
                <div>Content</div>
            </AppLayout>,
        )

        expect(screen.getByText('Finance Analyzer')).toBeInTheDocument()
    })

    it('renders navigation links', () => {
        renderComponent(
            <AppLayout>
                <div>Content</div>
            </AppLayout>,
        )

        expect(screen.getByText('Dashboard')).toBeInTheDocument()
        expect(screen.getByText('Upload')).toBeInTheDocument()
        expect(screen.getByText('Analysis')).toBeInTheDocument()
    })

    it('renders the logout button', () => {
        renderComponent(
            <AppLayout>
                <div>Content</div>
            </AppLayout>,
        )

        expect(screen.getByText('Logout')).toBeInTheDocument()
    })

    it('renders children content', () => {
        renderComponent(
            <AppLayout>
                <div>Test child content</div>
            </AppLayout>,
        )

        expect(screen.getByText('Test child content')).toBeInTheDocument()
    })

    it('highlights the active nav link based on current URL', () => {
        renderComponent(
            <AppLayout>
                <div>Content</div>
            </AppLayout>,
        )

        const dashboardLink = screen.getByText('Dashboard')
        expect(dashboardLink).toHaveClass('bg-gray-100')
    })
})
