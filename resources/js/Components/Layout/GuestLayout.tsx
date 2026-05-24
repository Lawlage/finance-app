import { type ReactNode } from 'react'

interface GuestLayoutProps {
    children: ReactNode
}

export default function GuestLayout({ children }: GuestLayoutProps) {
    return (
        <div className="flex min-h-screen items-center justify-center bg-gray-50">
            <div className="w-full max-w-md space-y-8 px-4">
                <div className="text-center">
                    <h1 className="text-3xl font-bold text-gray-900">
                        Finance Analyzer
                    </h1>
                    <p className="mt-2 text-gray-600">
                        Personal finance insights powered by AI
                    </p>
                </div>
                {children}
            </div>
        </div>
    )
}
