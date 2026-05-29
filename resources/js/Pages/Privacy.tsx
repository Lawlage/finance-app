import { useState } from 'react'
import { Head, useForm, router } from '@inertiajs/react'
import AppLayout from '@/Components/Layout/AppLayout'
import type { McpAccessLogEntry, ReplacementRule } from '@/types'
import type { SyntheticEvent } from 'react'

interface PrivacyProps {
    rules: ReplacementRule[]
    fallbackMode: string
    accountLabels: string[]
    auditLog: McpAccessLogEntry[]
}

export default function Privacy({
    rules,
    fallbackMode,
    accountLabels,
    auditLog,
}: PrivacyProps) {
    const ruleForm = useForm({ value: '', label: '' })
    const [deleting, setDeleting] = useState<number | null>(null)
    const [expanded, setExpanded] = useState<number | null>(null)
    const [editing, setEditing] = useState<number | null>(null)
    const [editValue, setEditValue] = useState('')
    const [editLabel, setEditLabel] = useState('')

    const mcpEndpoint =
        typeof window !== 'undefined'
            ? `${window.location.origin}/mcp/finance`
            : '/mcp/finance'

    function submitRule(e: SyntheticEvent) {
        e.preventDefault()
        ruleForm.post('/replacement-rules', {
            onSuccess: () => {
                ruleForm.reset()
            },
        })
    }

    function deleteRule(id: number) {
        setDeleting(id)
        router.delete(`/replacement-rules/${String(id)}`, {
            onFinish: () => {
                setDeleting(null)
            },
        })
    }

    function startEdit(rule: ReplacementRule) {
        setEditing(rule.id)
        setEditValue(rule.value)
        setEditLabel(rule.label)
    }

    function saveEdit(id: number) {
        if (!editValue.trim() || !editLabel.trim()) return
        router.patch(
            `/replacement-rules/${String(id)}`,
            { value: editValue.trim(), label: editLabel.trim() },
            {
                onSuccess: () => {
                    setEditing(null)
                },
            },
        )
    }

    function setFallback(mode: string) {
        router.patch('/privacy/settings', { fallback_mode: mode })
    }

    return (
        <AppLayout>
            <Head title="Privacy & MCP" />
            <h1 className="mb-6 text-2xl font-bold text-gray-900">
                Privacy &amp; MCP
            </h1>

            {/* Connect Claude */}
            <div className="mb-8 rounded-lg border border-gray-200 bg-white p-6">
                <h2 className="mb-2 text-lg font-semibold text-gray-900">
                    Connect Claude
                </h2>
                <p className="mb-3 text-sm text-gray-600">
                    Point your local Claude client (Claude Desktop / Code) at
                    the finance MCP server. Authenticate with a Sanctum personal
                    access token — the server is LAN-only and never exposed
                    publicly. The app runs in Docker, so run these from the
                    project directory on the host.
                </p>
                <dl className="space-y-3 text-sm">
                    <div>
                        <dt className="mb-1 font-medium text-gray-700">
                            Endpoint
                        </dt>
                        <dd>
                            <code className="rounded bg-gray-100 px-2 py-0.5 text-indigo-700">
                                {mcpEndpoint}
                            </code>
                        </dd>
                    </div>
                    <div>
                        <dt className="mb-1 font-medium text-gray-700">
                            1. Generate a Sanctum token
                        </dt>
                        <dd>
                            <code className="block overflow-x-auto rounded bg-gray-100 px-2 py-1 whitespace-pre text-gray-800">
                                docker compose exec app php artisan tinker
                                --execute=&quot;echo
                                App\Models\User::first()-&gt;createToken(&apos;claude&apos;)-&gt;plainTextToken;&quot;
                            </code>
                        </dd>
                    </div>
                    <div>
                        <dt className="mb-1 font-medium text-gray-700">
                            2. Register the server with Claude Code
                        </dt>
                        <dd>
                            <code className="block overflow-x-auto rounded bg-gray-100 px-2 py-1 whitespace-pre text-gray-800">
                                claude mcp add --transport http finance{' '}
                                {mcpEndpoint} --header &quot;Authorization:
                                Bearer &lt;token&gt;&quot;
                            </code>
                        </dd>
                    </div>
                </dl>
            </div>

            {/* Sanitization mode */}
            <div className="mb-8 rounded-lg border border-gray-200 bg-white p-6">
                <h2 className="mb-2 text-lg font-semibold text-gray-900">
                    Sanitization mode
                </h2>
                <p className="mb-4 text-sm text-gray-600">
                    How to handle sensitive values that are not in your
                    replacement map below.
                </p>
                <div className="space-y-3">
                    <label className="flex items-start gap-3">
                        <input
                            type="radio"
                            name="fallback_mode"
                            checked={fallbackMode === 'pseudonym'}
                            onChange={() => {
                                setFallback('pseudonym')
                            }}
                            className="mt-1"
                        />
                        <span className="text-sm">
                            <span className="font-medium text-gray-900">
                                Stable pseudonyms
                            </span>
                            <span className="block text-gray-600">
                                Replace with consistent tags like{' '}
                                <code className="rounded bg-gray-100 px-1">
                                    Person-2B
                                </code>
                                . Recurrence is preserved so Claude can spot
                                repeat payments — no identity is leaked.
                            </span>
                        </span>
                    </label>
                    <label className="flex items-start gap-3">
                        <input
                            type="radio"
                            name="fallback_mode"
                            checked={fallbackMode === 'redact'}
                            onChange={() => {
                                setFallback('redact')
                            }}
                            className="mt-1"
                        />
                        <span className="text-sm">
                            <span className="font-medium text-gray-900">
                                Flat redaction
                            </span>
                            <span className="block text-gray-600">
                                Replace with generic tokens like{' '}
                                <code className="rounded bg-gray-100 px-1">
                                    [NAME]
                                </code>
                                . Maximum privacy; recurring entities become
                                indistinguishable.
                            </span>
                        </span>
                    </label>
                </div>
            </div>

            <div className="grid gap-6 lg:grid-cols-2">
                {/* Replacement map */}
                <div className="rounded-lg border border-gray-200 bg-white p-6">
                    <h2 className="mb-1 text-lg font-semibold text-gray-900">
                        Replacement map
                    </h2>
                    <p className="mb-4 text-sm text-gray-600">
                        Account numbers and names you list here are swapped for
                        a friendly label everywhere before Claude sees them.
                        Stored encrypted.
                    </p>
                    <form onSubmit={submitRule} className="mb-4 flex gap-2">
                        <input
                            type="text"
                            placeholder="Account number or name"
                            value={ruleForm.data.value}
                            onChange={(e) => {
                                ruleForm.setData('value', e.target.value)
                            }}
                            className="flex-1 rounded-md border border-gray-300 px-3 py-2 text-sm"
                            required
                        />
                        <input
                            type="text"
                            placeholder="Label"
                            value={ruleForm.data.label}
                            onChange={(e) => {
                                ruleForm.setData('label', e.target.value)
                            }}
                            className="w-32 rounded-md border border-gray-300 px-3 py-2 text-sm"
                            required
                        />
                        <button
                            type="submit"
                            disabled={ruleForm.processing}
                            className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                        >
                            Add
                        </button>
                    </form>
                    <ul className="divide-y divide-gray-100">
                        {rules.length === 0 && (
                            <li className="py-2 text-sm text-gray-500">
                                No replacements yet.
                            </li>
                        )}
                        {rules.map((rule) =>
                            editing === rule.id ? (
                                <li key={rule.id} className="flex gap-2 py-2">
                                    <input
                                        type="text"
                                        value={editValue}
                                        onChange={(e) => {
                                            setEditValue(e.target.value)
                                        }}
                                        className="flex-1 rounded-md border border-gray-300 px-2 py-1 text-sm"
                                    />
                                    <input
                                        type="text"
                                        value={editLabel}
                                        onChange={(e) => {
                                            setEditLabel(e.target.value)
                                        }}
                                        className="w-32 rounded-md border border-gray-300 px-2 py-1 text-sm"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => {
                                            saveEdit(rule.id)
                                        }}
                                        className="text-sm font-medium text-indigo-600 hover:text-indigo-800"
                                    >
                                        Save
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setEditing(null)
                                        }}
                                        className="text-sm text-gray-500 hover:text-gray-700"
                                    >
                                        Cancel
                                    </button>
                                </li>
                            ) : (
                                <li
                                    key={rule.id}
                                    className="flex items-center justify-between py-2 text-sm"
                                >
                                    <span className="text-gray-700">
                                        <code className="rounded bg-gray-100 px-1">
                                            {rule.value}
                                        </code>{' '}
                                        →{' '}
                                        <span className="font-medium">
                                            {rule.label}
                                        </span>
                                    </span>
                                    <span className="flex gap-3">
                                        <button
                                            type="button"
                                            onClick={() => {
                                                startEdit(rule)
                                            }}
                                            className="text-indigo-600 hover:text-indigo-800"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => {
                                                deleteRule(rule.id)
                                            }}
                                            disabled={deleting === rule.id}
                                            className="text-red-600 hover:text-red-800 disabled:opacity-50"
                                        >
                                            Delete
                                        </button>
                                    </span>
                                </li>
                            ),
                        )}
                    </ul>
                    {accountLabels.length > 0 && (
                        <div className="mt-6 border-t border-gray-100 pt-4">
                            <h3 className="mb-2 text-sm font-medium text-gray-700">
                                Account labels in use
                            </h3>
                            <div className="flex flex-wrap gap-2">
                                {accountLabels.map((label) => (
                                    <span
                                        key={label}
                                        className="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600"
                                    >
                                        {label}
                                    </span>
                                ))}
                            </div>
                        </div>
                    )}
                </div>

                {/* Egress audit */}
                <div className="rounded-lg border border-gray-200 bg-white p-6">
                    <h2 className="mb-1 text-lg font-semibold text-gray-900">
                        What did Claude see?
                    </h2>
                    <p className="mb-4 text-sm text-gray-600">
                        The most recent data sent over MCP, exactly as it left
                        the app. Use this to verify only sanitized data is
                        shared.
                    </p>
                    <ul className="divide-y divide-gray-100">
                        {auditLog.length === 0 && (
                            <li className="py-2 text-sm text-gray-500">
                                No MCP activity yet.
                            </li>
                        )}
                        {auditLog.map((entry) => (
                            <li key={entry.id} className="py-2 text-sm">
                                <button
                                    type="button"
                                    onClick={() => {
                                        setExpanded(
                                            expanded === entry.id
                                                ? null
                                                : entry.id,
                                        )
                                    }}
                                    className="flex w-full items-center justify-between text-left"
                                >
                                    <span className="font-medium text-gray-700">
                                        <span className="mr-2 rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-600">
                                            {entry.primitive}
                                        </span>
                                        {entry.name}
                                    </span>
                                    <span className="text-xs text-gray-400">
                                        {entry.created_at}
                                    </span>
                                </button>
                                {expanded === entry.id && (
                                    <pre className="mt-2 max-h-64 overflow-auto rounded bg-gray-900 p-3 text-xs text-gray-100">
                                        {entry.payload}
                                    </pre>
                                )}
                            </li>
                        ))}
                    </ul>
                </div>
            </div>
        </AppLayout>
    )
}
