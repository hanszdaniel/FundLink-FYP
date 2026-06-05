<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\SharedAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    /**
     * Display all transactions + filters + export modal data
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $userId = $user->id;
        $sharedAccountIds = $this->getSharedAccountIds($user);

        $dateRange = $request->input('date_range', 'All Time');

        $transactions = $user->transactions()
            ->with(['category', 'sharedAccount'])
            ->when($request->filled('category_id') && $request->category_id !== 'all', function ($query) use ($request) {
                $query->where('category_id', $request->category_id);
            })
            ->when($request->filled('type') && $request->type !== 'All Types', function ($query) use ($request) {
                if ($request->type === 'Personal') {
                    $query->whereNull('shared_account_id');
                } elseif ($request->type === 'Shared') {
                    $query->whereNotNull('shared_account_id');
                } elseif (str_starts_with($request->type, 'shared:')) {
                    $sharedId = (int) substr($request->type, strlen('shared:'));
                    if ($sharedId > 0) {
                        $query->where('shared_account_id', $sharedId);
                    }
                }
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('description', 'like', '%' . $request->search . '%');
            })
            ->when($dateRange && $dateRange !== 'All Time', function ($query) use ($dateRange) {
                $today = Carbon::today();
                switch ($dateRange) {
                    case 'This Month':
                        $query->whereBetween('date', [
                            $today->copy()->startOfMonth(),
                            $today->copy()->endOfMonth(),
                        ]);
                        break;
                    case 'Last Month':
                        $lastMonthStart = $today->copy()->subMonth()->startOfMonth();
                        $lastMonthEnd = $today->copy()->subMonth()->endOfMonth();
                        $query->whereBetween('date', [$lastMonthStart, $lastMonthEnd]);
                        break;
                    case 'This Year':
                        $query->whereBetween('date', [
                            $today->copy()->startOfYear(),
                            $today->copy()->endOfYear(),
                        ]);
                        break;
                }
            })
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->get();

        $categories = Category::query()
            ->where('id', '!=', 'uncategorized')
            ->whereRaw('LOWER(name) != ?', ['uncategorized'])
            ->where(function ($query) use ($sharedAccountIds, $userId) {
                $query->where('user_id', $userId)
                    ->orWhereIn('id', function ($subQuery) use ($userId) {
                        $subQuery->select('category_id')
                            ->from('transactions')
                            ->where('user_id', $userId);
                    });
                if (!empty($sharedAccountIds)) {
                    $query->orWhereIn('id', function ($subQuery) use ($sharedAccountIds) {
                        $subQuery->select('category_id')
                            ->from('transactions')
                            ->whereIn('shared_account_id', $sharedAccountIds);
                    });
                }
            })
            ->get(['id', 'name']);
        $shared_accounts = $user->sharedAccounts()->get(['id', 'name']);

        // For export dropdown (personal + shared)
        $accounts = collect([
            (object)['id' => 0, 'name' => 'Personal Account'],
        ])->merge($shared_accounts);

        return view('transaction', compact(
            'transactions',
            'categories',
            'shared_accounts',
            'accounts'
        ));
    }

    /**
     * Export transactions to PDF
     */
    public function export(Request $request)
    {
        $user = auth()->user();

        $accountId = $request->input('account_id', 'all');
        $monthInput = $request->input('month');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $transactionsQuery = $user->transactions()
            ->with(['category', 'sharedAccount'])
            ->orderBy('date')
            ->orderBy('id');

        $accountScope = 'All Accounts';
        if ($accountId === '0') {
            $transactionsQuery->whereNull('shared_account_id');
            $accountScope = 'Personal Account';
        } elseif ($accountId !== 'all') {
            $sharedAccount = $user->sharedAccounts()->findOrFail($accountId);
            $transactionsQuery->where('shared_account_id', $sharedAccount->id);
            $accountScope = 'Shared: ' . $sharedAccount->name;
        }

        // Apply date filters only when provided (no implicit month filter)
        $statementDate = $monthInput ? now()->startOfMonth() : null;
        if ($monthInput) {
            try {
                $statementDate = Carbon::createFromFormat('Y-m', $monthInput)->startOfMonth();
            } catch (\Exception $e) {
                $statementDate = now()->startOfMonth();
            }

            $transactionsQuery->whereBetween('date', [
                $statementDate->copy()->startOfMonth(),
                $statementDate->copy()->endOfMonth(),
            ]);
        } elseif ($startDate || $endDate) {
            if ($startDate) {
                $transactionsQuery->whereDate('date', '>=', $startDate);
                $statementDate = Carbon::parse($startDate);
            }

            if ($endDate) {
                $transactionsQuery->whereDate('date', '<=', $endDate);
                $statementDate = Carbon::parse($endDate);
            }
        }

        $transactions = $transactionsQuery->get();

        // Generate running balance for the statement
        $balance = 0;
        foreach ($transactions as $t) {
            $balance += $t->amount;
            $t->running_balance = $balance;
        }

        // Gracefully handle missing statementDate (all-time export)
        $statementMonth = $statementDate
            ? $statementDate->format('F Y')
            : 'All Time';
        $fileName = "fundlink-statement-{$statementMonth}.pdf";

        $pdf = Pdf::loadView(
            'exports.statement',
            compact('transactions', 'user', 'statementMonth', 'accountScope')
        );

        if ($request->boolean('preview')) {
            return $pdf->stream($fileName);
        }

        return $pdf->download($fileName);
    }

    /**
     * Store a newly created transaction
     */
public function store(Request $request)
{
    $request->validate([
        'description' => 'required|string|max:255',
        'amount' => 'required|numeric|min:0.01',
        'date' => 'required|date',
        'category_id' => 'required|exists:categories,id',
        'shared_account_id' => 'nullable|numeric',
        'receipt_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
    ]);

    $user = auth()->user();
    $userId = $user->id;
    $sharedAccountIds = $this->getSharedAccountIds($user);
    $sharedAccountId = ($request->shared_account_id == 0) ? null : $request->shared_account_id;

    $this->ensureCategoryAccessible($request->category_id, $sharedAccountIds, $userId);

    DB::beginTransaction();

    try {
        $receiptPath = null;
        if ($request->hasFile('receipt_image')) {
            $receiptPath = $request->file('receipt_image')->store('transactions', 'public');
        }
        // 1️⃣ Save transaction
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'description' => $request->description,
            'amount' => $request->amount,
            'date' => $request->date,
            'category_id' => $request->category_id,
            'shared_account_id' => $sharedAccountId,
            'receipt_image_path' => $receiptPath,
        ]);

        // 2️⃣ Recalculate totals
        $this->recalculateCategoryTotals($transaction->category_id);
        $this->checkCategoryNotifications($transaction->category_id, $user);

        if ($sharedAccountId) {
            $this->recalculateSharedAccountTotals($sharedAccountId);
        }

        // 3) PERSONAL BUDGET NOTIFICATION
        $personalBudget = $user->personal_target_amount ?? 0;

        if ($personalBudget > 0) {

            $personalSpent = Transaction::where('user_id', $user->id)
                ->whereNull('shared_account_id')
                ->sum('amount');

            $percent = ($personalSpent / $personalBudget) * 100;
            $status = $this->statusForPercent($percent);

            // Near limit (>=75%)
            if (
                $status === 'near' &&
                $user->notify_budget_near &&
                !$user->notifications()
                    ->where('type', \App\Notifications\BudgetNearLimitNotification::class)
                    ->where('data->scope', 'personal')
                    ->exists()
            ) {
                $user->notify(
                    new \App\Notifications\BudgetNearLimitNotification(
                        'Personal Account',
                        round($percent, 1),
                        'personal',
                        null
                    )
                );
            }

            // Over limit (>=100%)
            if (
                $status === 'over' &&
                $user->notify_budget_over &&
                !$user->notifications()
                    ->where('type', \App\Notifications\BudgetOverLimitNotification::class)
                    ->where('data->scope', 'personal')
                    ->exists()
            ) {
                $user->notify(
                    new \App\Notifications\BudgetOverLimitNotification(
                        'Personal Account',
                        round($percent, 1),
                        'personal',
                        null
                    )
                );
            }
        }

        // 4) SHARED ACCOUNT NOTIFICATIONS
        if ($sharedAccountId) {
            $account = SharedAccount::find($sharedAccountId);
            if ($account) {
                $budget = $account->target_amount ?? 0;
                if ($budget > 0) {
                    $spent = Transaction::where('shared_account_id', $sharedAccountId)->sum('amount');
                    $percent = ($spent / $budget) * 100;
                    $status = $this->statusForPercent($percent);

                    $members = $account->members()->get();
                    foreach ($members as $member) {
                        // Fallback: if toggle columns are missing/NULL, treat as enabled so shared alerts still work
                        $sharedNearEnabled = property_exists($member, 'notify_shared_near')
                            ? ($member->notify_shared_near ?? true)
                            : true;
                        $sharedOverEnabled = property_exists($member, 'notify_shared_over')
                            ? ($member->notify_shared_over ?? true)
                            : true;

                        if (
                            $status === 'near' &&
                            $sharedNearEnabled &&
                            !$member->notifications()
                                ->where('type', \App\Notifications\BudgetNearLimitNotification::class)
                                ->where('data->scope', 'shared')
                                ->where('data->account_id', $sharedAccountId)
                                ->exists()
                        ) {
                            $member->notify(
                                new \App\Notifications\BudgetNearLimitNotification(
                                    $account->name ?? 'Shared Account',
                                    round($percent, 1),
                                    'shared',
                                    $sharedAccountId
                                )
                            );
                        }

                        if (
                            $status === 'over' &&
                            $sharedOverEnabled &&
                            !$member->notifications()
                                ->where('type', \App\Notifications\BudgetOverLimitNotification::class)
                                ->where('data->scope', 'shared')
                                ->where('data->account_id', $sharedAccountId)
                                ->exists()
                        ) {
                            $member->notify(
                                new \App\Notifications\BudgetOverLimitNotification(
                                    $account->name ?? 'Shared Account',
                                    round($percent, 1),
                                    'shared',
                                    $sharedAccountId
                                )
                            );
                        }
                    }
                }
            }
        }
        DB::commit();

        return response()->json([
            'message' => 'Transaction successfully recorded.',
            'transaction' => $transaction->load('category', 'sharedAccount'),
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Transaction Failed: ' . $e->getMessage());

        return response()->json([
            'error' => 'Transaction failed due to a server error.'
        ], 500);
    }
}




    /**
     * Update transaction
     */
    public function update(Request $request, Transaction $transaction)
    {
        if ($transaction->user_id !== auth()->id()) {
            abort(403);
        }

        $oldCategoryId = $transaction->category_id;
        $oldSharedAccountId = $transaction->shared_account_id;

        $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'category_id' => 'required|string|max:50|exists:categories,id',
            'shared_account_id' => 'nullable|numeric',
            'receipt_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $sharedAccountIds = $this->getSharedAccountIds(auth()->user());
        $this->ensureCategoryAccessible($request->category_id, $sharedAccountIds, auth()->id());

        $sharedAccountId = ($request->shared_account_id == 0) ? null : $request->shared_account_id;

        $receiptPath = $transaction->receipt_image_path;
        if ($request->hasFile('receipt_image')) {
            if ($receiptPath) {
                Storage::disk('public')->delete($receiptPath);
            }
            $receiptPath = $request->file('receipt_image')->store('transactions', 'public');
        }

        $transaction->update([
            'description' => $request->description,
            'amount' => $request->amount,
            'date' => $request->date,
            'category_id' => $request->category_id,
            'shared_account_id' => $sharedAccountId,
            'receipt_image_path' => $receiptPath,
        ]);

        $this->recalculateCategoryTotals($oldCategoryId);
        if ($oldCategoryId !== $transaction->category_id) {
            $this->recalculateCategoryTotals($transaction->category_id);
        }
        $this->checkCategoryNotifications($transaction->category_id, auth()->user());

        if ($oldSharedAccountId) {
            $this->recalculateSharedAccountTotals($oldSharedAccountId);
        }
        if ($sharedAccountId && $sharedAccountId !== $oldSharedAccountId) {
            $this->recalculateSharedAccountTotals($sharedAccountId);
        }

        return response()->json([
            'message' => 'Transaction updated.',
            'transaction' => $transaction->load('category', 'sharedAccount'),
        ]);
    }

    /**
     * Delete transaction
     */
    public function destroy(Transaction $transaction)
    {
        if ($transaction->user_id !== auth()->id()) {
            abort(403);
        }

        $categoryId = $transaction->category_id;
        $sharedAccountId = $transaction->shared_account_id;
        $receiptPath = $transaction->receipt_image_path;

        $transaction->delete();

        if ($receiptPath) {
            Storage::disk('public')->delete($receiptPath);
        }

        $this->recalculateCategoryTotals($categoryId);
        if ($sharedAccountId) {
            $this->recalculateSharedAccountTotals($sharedAccountId);
        }

        return response()->json(['message' => 'Transaction deleted.']);
    }

    /**
     * Personal transactions (for modal view)
     */
    public function personalTransactions()
    {
        $user = auth()->user();

        $txs = $user->transactions()
            ->whereNull('shared_account_id')
            ->with('category:id,name')
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->get();

        $current = $txs->sum('amount');

        return response()->json([
            'success' => true,
            'summary' => [
                'current_amount' => $current,
            ],
            'transactions' => $txs->map(function ($tx) {
                return [
                    'description' => $tx->description,
                    'category'    => $tx->category->name ?? 'Uncategorized',
                    'date'        => $tx->date ? Carbon::parse($tx->date)->format('d M Y') : null,
                    'time'        => $tx->created_at ? $tx->created_at->format('h:i A') : null,
                    'amount'      => $tx->amount,
                ];
            }),
        ]);
    }

    private function recalculateCategoryTotals(string $categoryId): void
    {
        $category = Category::find($categoryId);
        if (!$category) {
            return;
        }

        $spent = Transaction::where('category_id', $categoryId)->sum('amount');
        $budget = $category->budget ?? 0;
        $percent = $budget > 0 ? (int) round(($spent / $budget) * 100) : 0;

        $category->update([
            'spent' => $spent,
            'percent' => $percent,
        ]);
    }

    private function checkCategoryNotifications(string $categoryId, $user): void
    {
        $category = Category::find($categoryId);
        if (!$category) {
            return;
        }

        $budget = $category->budget ?? 0;
        if ($budget <= 0) {
            return;
        }

        $spent = Transaction::where('user_id', $user->id)
            ->where('category_id', $categoryId)
            ->sum('amount');
        $percent = ($spent / $budget) * 100;
        $status = $this->statusForPercent($percent);

        $nearEnabled = property_exists($user, 'notify_category_near')
            ? ($user->notify_category_near ?? true)
            : true;
        $overEnabled = property_exists($user, 'notify_category_over')
            ? ($user->notify_category_over ?? true)
            : true;

        if (
            $status === 'near' &&
            $nearEnabled &&
            !$user->notifications()
                ->where('type', \App\Notifications\BudgetNearLimitNotification::class)
                ->where('data->scope', 'category')
                ->where('data->account_id', $categoryId)
                ->exists()
        ) {
            $user->notify(
                new \App\Notifications\BudgetNearLimitNotification(
                    $category->name ?? 'Category',
                    round($percent, 1),
                    'category',
                    $categoryId
                )
            );
        }

        if (
            $status === 'over' &&
            $overEnabled &&
            !$user->notifications()
                ->where('type', \App\Notifications\BudgetOverLimitNotification::class)
                ->where('data->scope', 'category')
                ->where('data->account_id', $categoryId)
                ->exists()
        ) {
            $user->notify(
                new \App\Notifications\BudgetOverLimitNotification(
                    $category->name ?? 'Category',
                    round($percent, 1),
                    'category',
                    $categoryId
                )
            );
        }
    }

    private function recalculateSharedAccountTotals(int $sharedAccountId): void
    {
        $account = SharedAccount::find($sharedAccountId);
        if (!$account) {
            return;
        }

        $total = Transaction::where('shared_account_id', $sharedAccountId)->sum('amount');
        $account->update(['current_total' => $total]);
    }
    private function statusForPercent(float $percent): string
{
    if ($percent >= 100) {
        return 'over';
    }

    if ($percent >= 75) {
        return 'near';
    }

    return 'in';
}

    private function getSharedAccountIds($user): array
    {
        if (!$user) {
            return [];
        }

        return $user->sharedAccounts()
            ->pluck('shared_accounts.id')
            ->unique()
            ->values()
            ->all();
    }

    private function ensureCategoryAccessible(string $categoryId, array $sharedAccountIds, int $userId): void
    {
        $exists = Category::query()
            ->where('id', $categoryId)
            ->where(function ($query) use ($sharedAccountIds, $userId) {
                $query->where('user_id', $userId)
                    ->orWhereIn('id', function ($subQuery) use ($userId) {
                        $subQuery->select('category_id')
                            ->from('transactions')
                            ->where('user_id', $userId);
                    });
                if (!empty($sharedAccountIds)) {
                    $query->orWhereIn('id', function ($subQuery) use ($sharedAccountIds) {
                        $subQuery->select('category_id')
                            ->from('transactions')
                            ->whereIn('shared_account_id', $sharedAccountIds);
                    });
                }
            })
            ->exists();

        if (!$exists) {
            throw ValidationException::withMessages([
                'category_id' => 'Category is not available for this account.',
            ]);
        }
    }

}
