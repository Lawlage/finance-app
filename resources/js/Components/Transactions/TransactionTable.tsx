import type { Transaction } from '@/types'

interface TransactionTableProps {
    transactions: Transaction[]
}

export default function TransactionTable({
    transactions,
}: TransactionTableProps) {
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
                                {transaction.date}
                            </td>
                            <td className="max-w-xs truncate px-4 py-3 text-sm text-gray-900">
                                {transaction.description}
                            </td>
                            <td className="px-4 py-3 text-sm whitespace-nowrap">
                                {transaction.category ? (
                                    <span className="inline-flex rounded-full bg-indigo-100 px-2 py-1 text-xs font-medium text-indigo-800">
                                        {transaction.category}
                                    </span>
                                ) : (
                                    <span className="text-gray-400">
                                        Uncategorized
                                    </span>
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
