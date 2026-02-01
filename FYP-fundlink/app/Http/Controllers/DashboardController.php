<?php

namespace App\Http\Controllers;

use App\Models\SharedAccount;
use App\Models\Transaction;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Show the user's application dashboard.
     */
    public function index()
    {
        $user = Auth::user();
        $userId = $user->id;
        $sharedAccountIds = $user->sharedAccounts()
            ->pluck('shared_accounts.id')
            ->unique()
            ->values();

        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd   = $today->copy()->endOfMonth();

        // ==========================
        // 🔔 NOTIFICATIONS
        // ==========================
        $notifications = $user->notifications()
            ->latest()
            ->take(5)
            ->get();

        $unreadCount = $user->unreadNotifications()->count();

        // ==========================
        // Personal stats
        // ==========================
        $personalBudget = $user->personal_target_amount ?? 0;

        $personalSpent = Transaction::where('user_id', $user->id)
            ->whereNull('shared_account_id')
            ->sum('amount');

        $personalPercent = $personalBudget > 0
            ? min(round(($personalSpent / $personalBudget) * 100, 1), 999)
            : 0;

        $personalStatus = $this->statusForPercent($personalPercent);
        $personalRemaining = max($personalBudget - $personalSpent, 0);

        // ==========================
        // Shared accounts
        // ==========================
        $sharedAccounts = $user->sharedAccounts()
            ->with(['members:id,name'])
            ->withSum('transactions as total_spent', 'amount')
            ->get()
            ->map(function (SharedAccount $acc) {
                $spent = $acc->total_spent ?? 0;
                $budget = $acc->target_amount ?? 0;
                $percent = $budget > 0
                    ? min(round(($spent / $budget) * 100, 1), 999)
                    : 0;

                return [
                    'id' => $acc->id,
                    'name' => $acc->name,
                    'description' => $acc->description,
                    'budget' => $budget,
                    'spent' => $spent,
                    'percent' => $percent,
                    'status' => $this->statusForPercent($percent),
                    'members_count' => $acc->members->count(),
                ];
            });

        $primaryAccount = $sharedAccounts->first();

        // ==========================
        // Monthly breakdown
        // ==========================
        $monthlyTx = $user->transactions()
            ->with('category')
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->get();

        $monthlyTotal = $monthlyTx->sum('amount');

        $categoryBreakdown = $monthlyTx
            ->groupBy(fn ($tx) => $tx->category->name ?? 'Uncategorized')
            ->map->sum('amount');

        $breakdownList = $categoryBreakdown->map(function ($amount, $label) use ($monthlyTotal) {
            $percent = $monthlyTotal > 0
                ? round(($amount / $monthlyTotal) * 100, 1)
                : 0;

            return [
                'label' => $label,
                'amount' => round($amount, 2),
                'percent' => $percent,
            ];
        })->values();

        $expenseChartData = [
            'labels' => $categoryBreakdown->keys()->values(),
            'amounts' => $categoryBreakdown->values()->map(fn ($v) => round($v, 2))->values(),
            'colors' => [
                "#3A478C", "#6C5CE7", "#00B894", "#FDCD6E",
                "#E17055", "#0984E3", "#d9534f", "#f0ad4e"
            ],
            'currency' => 'RM',
            'month' => $today->format('F'),
            'total' => round($monthlyTotal, 2),
        ];

        // ==========================
        // Recent transactions
        // ==========================
        $recentTransactions = $user->transactions()
            ->with(['category', 'sharedAccount'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        // ==========================
        // Categories & account options
        // ==========================
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

        $accountOptions = collect([
            ['id' => 0, 'name' => 'Personal Account']
        ])->merge(
            $sharedAccounts->map(fn ($acc) => [
                'id' => $acc['id'],
                'name' => $acc['name'],
            ])
        );

        // ==========================
        // RETURN VIEW
        // ==========================
        return view('dashboard', [
            'userName' => $user->name,
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'primaryAccount' => $primaryAccount,
            'personalStats' => [
                'budget' => $personalBudget,
                'spent' => $personalSpent,
                'remaining' => $personalRemaining,
                'percent' => $personalPercent,
                'status' => $personalStatus,
            ],
            'sharedAccounts' => $sharedAccounts,
            'monthlyTotal' => $monthlyTotal,
            'breakdownList' => $breakdownList,
            'expenseChartData' => $expenseChartData,
            'recentTransactions' => $recentTransactions,
            'categories' => $categories,
            'accountOptions' => $accountOptions,
        ]);
    }

    /**
     * Convert percentage to status
     */
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
}
