import { describe, it, expect } from 'vitest'
import { renderComponent, screen } from '@/test/utils'
import GuestLayout from './GuestLayout'

describe('GuestLayout', () => {
    it('renders the app name heading', () => {
        renderComponent(
            <GuestLayout>
                <div>Content</div>
            </GuestLayout>,
        )

        expect(screen.getByText('Finance Analyzer')).toBeInTheDocument()
    })

    it('renders the tagline', () => {
        renderComponent(
            <GuestLayout>
                <div>Content</div>
            </GuestLayout>,
        )

        expect(
            screen.getByText('Personal finance insights powered by AI'),
        ).toBeInTheDocument()
    })

    it('renders children content', () => {
        renderComponent(
            <GuestLayout>
                <div>Guest child content</div>
            </GuestLayout>,
        )

        expect(screen.getByText('Guest child content')).toBeInTheDocument()
    })
})
