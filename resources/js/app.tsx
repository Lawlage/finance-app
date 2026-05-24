import '../css/app.css'
import type React from 'react'
import { createInertiaApp } from '@inertiajs/react'
import { createRoot } from 'react-dom/client'

void createInertiaApp({
    title: (title) =>
        title ? `${title} - Finance Analyzer` : 'Finance Analyzer',
    resolve: (name) => {
        const pages = import.meta.glob<{ default: React.ComponentType }>(
            ['./Pages/**/*.tsx', '!**/*.test.tsx'],
            { eager: true },
        )
        return pages[`./Pages/${name}.tsx`]
    },
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />)
    },
})
