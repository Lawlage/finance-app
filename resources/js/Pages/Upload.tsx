import { Head, useForm } from '@inertiajs/react'
import AppLayout from '@/Components/Layout/AppLayout'
import FileDropzone from '@/Components/Upload/FileDropzone'
import type { SyntheticEvent } from 'react'

export default function Upload() {
    const { data, setData, post, processing, errors, recentlySuccessful } =
        useForm<{
            statement: File | null
            account: string
        }>({
            statement: null,
            account: '',
        })

    function submit(e: SyntheticEvent) {
        e.preventDefault()
        if (!data.statement) return

        post('/upload', {
            forceFormData: true,
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
        </AppLayout>
    )
}
