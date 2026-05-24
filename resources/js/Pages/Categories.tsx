import { useState } from 'react'
import { Head, useForm, router } from '@inertiajs/react'
import AppLayout from '@/Components/Layout/AppLayout'
import type { Category, CategoryRule } from '@/types'
import type { SyntheticEvent } from 'react'

interface CategoriesProps {
    categories: Category[]
    rules: CategoryRule[]
}

export default function Categories({ categories, rules }: CategoriesProps) {
    const categoryForm = useForm({ name: '' })
    const ruleForm = useForm({ category: '', pattern: '' })
    const [deletingRule, setDeletingRule] = useState<number | null>(null)
    const [deletingCategory, setDeletingCategory] = useState<number | null>(
        null,
    )
    const [editingCategory, setEditingCategory] = useState<number | null>(null)
    const [editValue, setEditValue] = useState('')
    const [editingRule, setEditingRule] = useState<number | null>(null)
    const [editRulePattern, setEditRulePattern] = useState('')
    const [editRuleCategory, setEditRuleCategory] = useState('')

    function submitCategory(e: SyntheticEvent) {
        e.preventDefault()
        categoryForm.post('/categories', {
            onSuccess: () => {
                categoryForm.reset()
            },
        })
    }

    function submitRule(e: SyntheticEvent) {
        e.preventDefault()
        ruleForm.post('/category-rules', {
            onSuccess: () => {
                ruleForm.reset()
            },
        })
    }

    function startEditCategory(category: Category) {
        setEditingCategory(category.id)
        setEditValue(category.name)
    }

    function saveCategory(id: number) {
        if (!editValue.trim()) return
        router.patch(
            `/categories/${String(id)}`,
            { name: editValue.trim() },
            {
                onSuccess: () => {
                    setEditingCategory(null)
                    setEditValue('')
                },
            },
        )
    }

    function deleteCategory(id: number) {
        setDeletingCategory(id)
        router.delete(`/categories/${String(id)}`, {
            onFinish: () => {
                setDeletingCategory(null)
            },
        })
    }

    function startEditRule(rule: CategoryRule) {
        setEditingRule(rule.id)
        setEditRulePattern(rule.pattern)
        setEditRuleCategory(rule.category)
    }

    function saveRule(id: number) {
        if (!editRulePattern.trim() || !editRuleCategory.trim()) return
        router.patch(
            `/category-rules/${String(id)}`,
            {
                pattern: editRulePattern.trim(),
                category: editRuleCategory.trim(),
            },
            {
                onSuccess: () => {
                    setEditingRule(null)
                },
            },
        )
    }

    function deleteRule(id: number) {
        setDeletingRule(id)
        router.delete(`/category-rules/${String(id)}`, {
            onFinish: () => {
                setDeletingRule(null)
            },
        })
    }

    const [recategorizing, setRecategorizing] = useState(false)

    function recategorize() {
        setRecategorizing(true)
        router.post(
            '/categories/recategorize',
            {},
            {
                onFinish: () => {
                    setRecategorizing(false)
                },
            },
        )
    }

    return (
        <AppLayout>
            <Head title="Categories" />
            <div className="mb-6 flex items-center justify-between">
                <h1 className="text-2xl font-bold text-gray-900">
                    Categories & Rules
                </h1>
                <button
                    type="button"
                    onClick={recategorize}
                    disabled={recategorizing || rules.length === 0}
                    className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                >
                    {recategorizing ? 'Recategorizing...' : 'Recategorize All'}
                </button>
            </div>

            <div className="grid gap-6 lg:grid-cols-2">
                {/* Categories Section */}
                <div className="rounded-lg border border-gray-200 bg-white p-6">
                    <h2 className="mb-4 text-lg font-semibold text-gray-900">
                        Categories
                    </h2>
                    <form onSubmit={submitCategory} className="mb-4 flex gap-2">
                        <input
                            type="text"
                            value={categoryForm.data.name}
                            onChange={(e) => {
                                categoryForm.setData('name', e.target.value)
                            }}
                            placeholder="New category name"
                            className="flex-1 rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:outline-none"
                            required
                        />
                        <button
                            type="submit"
                            disabled={categoryForm.processing}
                            className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                        >
                            Add
                        </button>
                    </form>
                    {categoryForm.errors.name && (
                        <p className="mb-2 text-sm text-red-600">
                            {categoryForm.errors.name}
                        </p>
                    )}
                    {categories.length === 0 ? (
                        <p className="text-sm text-gray-500">
                            No categories yet.
                        </p>
                    ) : (
                        <div className="flex flex-wrap gap-2">
                            {categories.map((category) => (
                                <span
                                    key={category.id}
                                    className="inline-flex items-center gap-1 rounded-full bg-indigo-100 px-3 py-1 text-sm font-medium text-indigo-800"
                                >
                                    {editingCategory === category.id ? (
                                        <span className="flex items-center gap-1">
                                            <input
                                                type="text"
                                                value={editValue}
                                                onChange={(e) => {
                                                    setEditValue(e.target.value)
                                                }}
                                                onKeyDown={(e) => {
                                                    if (e.key === 'Enter') {
                                                        saveCategory(
                                                            category.id,
                                                        )
                                                    }
                                                    if (e.key === 'Escape') {
                                                        setEditingCategory(null)
                                                    }
                                                }}
                                                className="w-28 rounded border border-indigo-300 bg-white px-2 py-0.5 text-xs focus:border-indigo-500 focus:outline-none"
                                                autoFocus
                                            />
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    saveCategory(category.id)
                                                }}
                                                className="text-green-600 hover:text-green-800"
                                            >
                                                &#10003;
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    setEditingCategory(null)
                                                }}
                                                className="text-gray-400 hover:text-gray-600"
                                            >
                                                &times;
                                            </button>
                                        </span>
                                    ) : (
                                        <>
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    startEditCategory(category)
                                                }}
                                                className="hover:text-indigo-600"
                                                title="Click to rename"
                                            >
                                                {category.name}
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    deleteCategory(category.id)
                                                }}
                                                disabled={
                                                    deletingCategory ===
                                                    category.id
                                                }
                                                className="ml-1 text-indigo-600 hover:text-indigo-900"
                                                aria-label={`Delete ${category.name}`}
                                            >
                                                &times;
                                            </button>
                                        </>
                                    )}
                                </span>
                            ))}
                        </div>
                    )}
                </div>

                {/* Rules Section */}
                <div className="rounded-lg border border-gray-200 bg-white p-6">
                    <h2 className="mb-4 text-lg font-semibold text-gray-900">
                        Auto-Categorization Rules
                    </h2>
                    <p className="mb-4 text-sm text-gray-500">
                        Transactions containing the pattern will be
                        automatically assigned to the category.
                    </p>
                    <form onSubmit={submitRule} className="mb-4 space-y-2">
                        <div className="flex gap-2">
                            <input
                                type="text"
                                value={ruleForm.data.pattern}
                                onChange={(e) => {
                                    ruleForm.setData('pattern', e.target.value)
                                }}
                                placeholder="Pattern (e.g. Bakery)"
                                className="flex-1 rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:outline-none"
                                required
                            />
                            <input
                                type="text"
                                value={ruleForm.data.category}
                                onChange={(e) => {
                                    ruleForm.setData('category', e.target.value)
                                }}
                                placeholder="Category"
                                className="flex-1 rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:outline-none"
                                required
                                list="category-options"
                            />
                            <datalist id="category-options">
                                {categories.map((c) => (
                                    <option key={c.id} value={c.name} />
                                ))}
                            </datalist>
                            <button
                                type="submit"
                                disabled={ruleForm.processing}
                                className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                            >
                                Add Rule
                            </button>
                        </div>
                        {(ruleForm.errors.pattern ??
                            ruleForm.errors.category) && (
                            <p className="text-sm text-red-600">
                                {ruleForm.errors.pattern ??
                                    ruleForm.errors.category}
                            </p>
                        )}
                    </form>

                    {rules.length === 0 ? (
                        <p className="text-sm text-gray-500">No rules yet.</p>
                    ) : (
                        <div className="divide-y divide-gray-100">
                            {rules.map((rule) => (
                                <div
                                    key={rule.id}
                                    className="flex items-center justify-between gap-2 py-2"
                                >
                                    {editingRule === rule.id ? (
                                        <>
                                            <div className="flex flex-1 items-center gap-2">
                                                <input
                                                    type="text"
                                                    value={editRulePattern}
                                                    onChange={(e) => {
                                                        setEditRulePattern(
                                                            e.target.value,
                                                        )
                                                    }}
                                                    onKeyDown={(e) => {
                                                        if (e.key === 'Escape')
                                                            setEditingRule(null)
                                                    }}
                                                    className="w-32 rounded border border-gray-300 px-2 py-1 text-xs focus:border-indigo-500 focus:outline-none"
                                                    autoFocus
                                                />
                                                <span className="text-gray-400">
                                                    &rarr;
                                                </span>
                                                <input
                                                    type="text"
                                                    value={editRuleCategory}
                                                    onChange={(e) => {
                                                        setEditRuleCategory(
                                                            e.target.value,
                                                        )
                                                    }}
                                                    onKeyDown={(e) => {
                                                        if (e.key === 'Enter')
                                                            saveRule(rule.id)
                                                        if (e.key === 'Escape')
                                                            setEditingRule(null)
                                                    }}
                                                    className="w-32 rounded border border-gray-300 px-2 py-1 text-xs focus:border-indigo-500 focus:outline-none"
                                                    list="edit-category-options"
                                                />
                                            </div>
                                            <div className="flex gap-2">
                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        saveRule(rule.id)
                                                    }}
                                                    className="text-sm text-green-600 hover:text-green-800"
                                                >
                                                    Save
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        setEditingRule(null)
                                                    }}
                                                    className="text-sm text-gray-400 hover:text-gray-600"
                                                >
                                                    Cancel
                                                </button>
                                            </div>
                                        </>
                                    ) : (
                                        <>
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    startEditRule(rule)
                                                }}
                                                className="text-left text-sm hover:opacity-75"
                                                title="Click to edit"
                                            >
                                                <span className="font-medium text-gray-900">
                                                    &ldquo;{rule.pattern}
                                                    &rdquo;
                                                </span>
                                                <span className="mx-2 text-gray-400">
                                                    &rarr;
                                                </span>
                                                <span className="inline-flex rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-800">
                                                    {rule.category}
                                                </span>
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    deleteRule(rule.id)
                                                }}
                                                disabled={
                                                    deletingRule === rule.id
                                                }
                                                className="text-sm text-red-600 hover:text-red-800"
                                            >
                                                Delete
                                            </button>
                                        </>
                                    )}
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
            <datalist id="edit-category-options">
                {categories.map((c) => (
                    <option key={c.id} value={c.name} />
                ))}
            </datalist>
        </AppLayout>
    )
}
