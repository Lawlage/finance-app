import { useState } from 'react'
import { router } from '@inertiajs/react'
import type { Transaction } from '@/types'
import { formatDate } from '@/utils/date'

interface TransactionTableProps {
    transactions: Transaction[]
}

export default function TransactionTable({
    transactions,
}: TransactionTableProps) {
    const [editingId, setEditingId] = useState<number | null>(null)
    const [editValue, setEditValue] = useState('')

    function startEdit(transaction: Transaction) {
        setEditingId(transaction.id)
        setEditValue(transaction.category ?? '')
    }

    function saveCategory(transactionId: number) {
        if (!editValue.trim()) return

        router.patch(
            `/transactions/${String(transactionId)}/category`,
            { category: editValue.trim() },
            {
                onSuccess: () => {
                    setEditingId(null)
                    setEditValue('')
                },
            },
        )
    }

    if (transactions.length === 0) {
        return (
            <div className="py-8 text-center text-gray-500">
                No transactions yet. Upload a statement to get started.
            </div>
        )
    }

    return (
        <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                    <tr>
                        <th className="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                            Date
                        </th>
                        <th className="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                            Description
                        </th>
                        <th className="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                            Category
                        </th>
                        <th className="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                            Account
                        </th>
                        <th className="px-4 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase">
                            Amount
                        </th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-gray-200 bg-white">
                    {transactions.map((transaction) => (
                        <tr key={transaction.id}>
                            <td className="px-4 py-3 text-sm whitespace-nowrap text-gray-900">
                                {formatDate(transaction.date)}
                            </td>
                            <td className="max-w-xs truncate px-4 py-3 text-sm text-gray-900">
                                {transaction.description}
                            </td>
                            <td className="px-4 py-3 text-sm whitespace-nowrap">
                                {editingId === transaction.id ? (
                                    <div className="flex items-center gap-1">
                                        <input
                                            type="text"
                                            value={editValue}
                                            onChange={(e) => {
                                                setEditValue(e.target.value)
                                            }}
                                            onKeyDown={(e) => {
                                                if (e.key === 'Enter') {
                                                    saveCategory(transaction.id)
                                                }
                                                if (e.key === 'Escape') {
                                                    setEditingId(null)
                                                }
                                            }}
                                            className="w-28 rounded border border-gray-300 px-2 py-1 text-xs focus:border-indigo-500 focus:outline-none"
                                            autoFocus
                                        />
                                        <button
                                            type="button"
                                            onClick={() => {
                                                saveCategory(transaction.id)
                                            }}
                                            className="text-xs text-green-600 hover:text-green-800"
                                        >
                                            Save
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => {
                                                setEditingId(null)
                                            }}
                                            className="text-xs text-gray-400 hover:text-gray-600"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                ) : (
                                    <button
                                        type="button"
                                        onClick={() => {
                                            startEdit(transaction)
                                        }}
                                        className="cursor-pointer"
                                        title={
                                            transaction.category_locked
                                                ? 'Manually set (click to change)'
                                                : 'Click to override category'
                                        }
                                    >
                                        {transaction.category ? (
                                            <span
                                                className={`inline-flex rounded-full px-2 py-1 text-xs font-medium ${
                                                    transaction.category.toLowerCase() ===
                                                    'other'
                                                        ? 'bg-gray-200 text-gray-500'
                                                        : transaction.category_locked
                                                          ? 'bg-amber-100 text-amber-800'
                                                          : 'bg-indigo-100 text-indigo-800'
                                                }`}
                                            >
                                                {transaction.category}
                                                {transaction.category_locked && (
                                                    <span
                                                        className="ml-1"
                                                        title="Manually overridden"
                                                    >
                                                        &#128274;
                                                    </span>
                                                )}
                                            </span>
                                        ) : (
                                            <span className="text-gray-400 hover:text-indigo-600">
                                                Uncategorized
                                            </span>
                                        )}
                                    </button>
                                )}
                            </td>
                            <td className="px-4 py-3 text-sm whitespace-nowrap text-gray-600">
                                {transaction.account}
                            </td>
                            <td
                                className={`px-4 py-3 text-right text-sm font-medium whitespace-nowrap ${
                                    transaction.amount < 0
                                        ? 'text-red-600'
                                        : 'text-green-600'
                                }`}
                            >
                                ${Math.abs(transaction.amount).toFixed(2)}
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    )
}
