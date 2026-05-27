<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CategoryRule;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        $categories = Category::orderBy('name')->get();
        $rules = CategoryRule::orderBy('category')->orderBy('pattern')->get();

        return Inertia::render('Categories', [
            'categories' => $categories,
            'rules' => $rules,
        ]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
        ]);

        Category::create($validated);

        return redirect()->back()->with('success', 'Category created.');
    }

    public function updateCategory(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name,'.$category->id],
        ]);

        $oldName = $category->name;
        $category->update($validated);

        Transaction::where('category', $oldName)
            ->update(['category' => $validated['name']]);

        CategoryRule::where('category', $oldName)
            ->update(['category' => $validated['name']]);

        return redirect()->back()->with('success', 'Category renamed.');
    }

    public function destroyCategory(Category $category): RedirectResponse
    {
        $category->delete();

        return redirect()->back()->with('success', 'Category deleted.');
    }

    public function storeRule(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category' => ['required', 'string', 'max:255'],
            'pattern' => ['required', 'string', 'max:255'],
        ]);

        CategoryRule::create($validated);
        $count = $this->applyRules();

        return redirect()->back()->with('success', "Rule created. {$count} transactions recategorized.");
    }

    public function updateRule(Request $request, CategoryRule $categoryRule): RedirectResponse
    {
        $validated = $request->validate([
            'category' => ['required', 'string', 'max:255'],
            'pattern' => ['required', 'string', 'max:255'],
        ]);

        $categoryRule->update($validated);
        $count = $this->applyRules();

        return redirect()->back()->with('success', "Rule updated. {$count} transactions recategorized.");
    }

    public function destroyRule(CategoryRule $categoryRule): RedirectResponse
    {
        $categoryRule->delete();

        return redirect()->back()->with('success', 'Rule deleted.');
    }

    public function recategorize(): RedirectResponse
    {
        $count = $this->applyRules();

        return redirect()->back()->with('success', "{$count} transactions recategorized.");
    }

    private function applyRules(): int
    {
        $rules = CategoryRule::all();

        if ($rules->isEmpty()) {
            return 0;
        }

        $transactions = Transaction::where('category_locked', false)->get();
        $updated = 0;

        foreach ($transactions as $transaction) {
            foreach ($rules as $rule) {
                if (str_contains(strtolower((string) $transaction->description), strtolower($rule->pattern))) {
                    if ($transaction->category !== $rule->category) {
                        $transaction->update(['category' => $rule->category]);
                        $updated++;
                    }

                    break;
                }
            }
        }

        return $updated;
    }
}
