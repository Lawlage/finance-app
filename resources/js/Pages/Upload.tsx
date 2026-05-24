import { Head, useForm, router } from '@inertiajs/react'
import { useState } from 'react'
import AppLayout from '@/Components/Layout/AppLayout'
import FileDropzone from '@/Components/Upload/FileDropzone'
import { formatDateTime } from '@/utils/date'
import type { Import } from '@/types'
import type { SyntheticEvent } from 'react'

interface UploadProps {
    imports?: Import[]
}

export default function Upload({ imports = [] }: UploadProps) {
    const { data, setData, post, processing, errors, recentlySuccessful } =
        useForm<{
            statement: File | null
            account: string
        }>({
            statement: null,
            account: '',
        })
    const [deletingId, setDeletingId] = useState<number | null>(null)

    function submit(e: SyntheticEvent) {
        e.preventDefault()
        if (!data.statement) return

        post('/upload', {
            forceFormData: true,
        })
    }

    function deleteImport(id: number) {
        setDeletingId(id)
        router.delete(`/imports/${String(id)}`, {
            onFinish: () => {
                setDeletingId(null)
            },
        })
    }

    return (
        <AppLayout>
            <Head title="Upload Statement" />
            <h1 className="mb-6 text-2xl font-bold text-gray-900">
                Upload Bank Statement
            </h1>

            <div className="rounded-lg border border-gray-200 bg-white p-6">
                <form onSubmit={submit} className="space-y-6">
                    <div>
                        <label
                            htmlFor="account"
                            className="block text-sm font-medium text-gray-700"
                        >
                            Account Name
                        </label>
                        <input
                            id="account"
                            type="text"
                            value={data.account}
                            onChange={(e) => {
                                setData('account', e.target.value)
                            }}
                            placeholder="e.g. ANZ Everyday"
                            className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:outline-none"
                            required
                        />
                        {errors.account && (
                            <p className="mt-1 text-sm text-red-600">
                                {errors.account}
                            </p>
                        )}
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700">
                            Statement File
                        </label>
                        <div className="mt-1">
                            <FileDropzone
                                accept=".pdf,.csv"
                                onFileSelect={(file) => {
                                    setData('statement', file)
                                }}
                            />
                        </div>
                        {errors.statement && (
                            <p className="mt-1 text-sm text-red-600">
                                {errors.statement}
                            </p>
                        )}
                    </div>

                    <button
                        type="submit"
                        disabled={processing || !data.statement}
                        className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none disabled:opacity-50"
                    >
                        {processing ? 'Uploading...' : 'Upload & Process'}
                    </button>

                    {recentlySuccessful && (
                        <p className="text-sm text-green-600">
                            Statement uploaded successfully! Processing has been
                            queued.
                        </p>
                    )}
                </form>
            </div>

            {imports.length > 0 && (
                <div className="mt-8 rounded-lg border border-gray-200 bg-white">
                    <div className="border-b border-gray-200 px-6 py-4">
                        <h2 className="text-lg font-semibold text-gray-900">
                            Import History
                        </h2>
                    </div>
                    <div className="divide-y divide-gray-200">
                        {imports.map((imp) => (
                            <div
                                key={imp.id}
                                className="flex items-center justify-between px-6 py-4"
                            >
                                <div>
                                    <p className="text-sm font-medium text-gray-900">
                                        {imp.filename}
                                    </p>
                                    <p className="text-xs text-gray-500">
                                        {imp.account} &middot;{' '}
                                        {imp.transaction_count} transactions
                                        &middot;{' '}
                                        {formatDateTime(imp.created_at)}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    onClick={() => {
                                        deleteImport(imp.id)
                                    }}
                                    disabled={deletingId === imp.id}
                                    className="rounded-md border border-red-300 px-3 py-1 text-sm text-red-600 hover:bg-red-50 disabled:opacity-50"
                                >
                                    {deletingId === imp.id
                                        ? 'Deleting...'
                                        : 'Delete'}
                                </button>
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </AppLayout>
    )
}
