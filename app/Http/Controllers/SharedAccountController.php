<?php

namespace App\Http\Controllers;

use App\Models\SharedAccount;
use App\Models\SharedAccountInvite;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Notifications\SharedAccountInvite as InviteNotification;
use App\Notifications\SharedAccountJoinedNotification as JoinedNotification;

class SharedAccountController extends Controller
{
    // =========================================================================
    // SHARED ACCOUNTS DASHBOARD (INDEX PAGE)
    // =========================================================================
    public function index()
    {
        $user = Auth::user();

        // -----------------------------
        // PERSONAL ACCOUNT CALCULATION
        // -----------------------------
        $personalTotal = Transaction::where('user_id', $user->id)
            ->whereNull('shared_account_id')
            ->sum('amount');

        $personalTarget = $user->personal_target_amount ?? 0;

        $personalPercentage = $personalTarget > 0
            ? min(round(($personalTotal / $personalTarget) * 100, 2), 999)
            : 0;

        $personalRemaining = max($personalTarget - $personalTotal, 0);

        list($personalStatusText, $personalStatusColor, $personalStatusBg) =
            $this->determineStatus(true, $personalPercentage);

        $personalAccount = [
            'id' => 'personal',
            'name' => 'Personal Account',
            'description' => 'Your individual transactions and balances.',
            'type' => 'Personal',
            'limit_or_goal' => $personalTarget,
            'current_amount' => $personalTotal,
            'percentage' => $personalPercentage,
            'remaining' => $personalRemaining,
            'status_text' => $personalStatusText,
            'status_color' => $personalStatusColor,
            'status_bg' => $personalStatusBg,
            'members' => [],
            'can_delete' => false,
        ];

        // -----------------------------
        // SHARED ACCOUNTS LIST
        // -----------------------------
        $sharedAccounts = $user->sharedAccounts()
            ->where('shared_accounts.is_achieved', false)
            ->with('members:id,name')
            ->get();

        $formattedAccounts = $sharedAccounts->map(function ($account) use ($user) {
            $isExpense = $account->type === 'Expense';
            $limit = $account->target_amount;

            $current = $account->transactions()->sum('amount');

            $percentage = $limit > 0 ? round(($current / $limit) * 100) : 0;
            $remaining = abs($limit - $current);

            list($statusText, $statusColor, $statusBg) =
                $this->determineStatus($isExpense, $percentage);

            $isCreator = $account->creator_user_id === $user->id;

            return [
                'id' => $account->id,
                'name' => $account->name,
                'description' => $account->description,
                'type' => $account->type,
                'limit_or_goal' => $limit,
                'current_amount' => $current,
                'percentage' => $percentage,
                'remaining' => $remaining,
                'status_text' => $statusText,
                'status_color' => $statusColor,
                'status_bg' => $statusBg,
                'members' => $account->members->map(function ($m) {
                    return [
                        'name' => $m->name,
                        'initial' => strtoupper(substr($m->name, 0, 1)),
                        'color' => '#'.substr(md5($m->id), 0, 6),
                    ];
                }),
                'can_delete' => $isCreator,
                'can_leave' => !$isCreator,
            ];
        });

        return view('sharedaccount', [
            'accounts' => $formattedAccounts,
            'personalAccount' => $personalAccount,
        ]);
    }

    // Determine status styling based on account type and usage %
    private function determineStatus($isExpense, $percentage)
    {
        if ($isExpense) {
            if ($percentage >= 100) return ['OVER BUDGET', '#d9534f', '#f9e9e9'];
            if ($percentage >= 75)  return ['Near Limit', '#f0ad4e', '#fff8e6'];
            return ['On Track', '#5cb85c', '#e6f7e6'];
        }

        // Savings account logic
        if ($percentage >= 100) return ['Goal Achieved!', '#5cb85c', '#e6f7e6'];
        if ($percentage < 20)   return ['Behind Schedule', '#d9534f', '#f9e9e9'];
        return ['On Track', '#f0ad4e', '#fff8e6'];
    }

    // =========================================================================
    // DETAIL PAGE — FULL VIEW OF ONE SHARED ACCOUNT
    // =========================================================================
    public function detail($id)
    {
        $user = auth()->user();

        // Load shared account with members + transactions
        $sharedAccount = SharedAccount::with([
            'members',
            'transactions' => function ($q) {
                $q->with(['user', 'category'])
                  ->orderByDesc('date')
                  ->orderByDesc('created_at');
            },
        ])->findOrFail($id);

        // Authorisation
        if (
            $sharedAccount->creator_user_id !== $user->id &&
            !$sharedAccount->members->contains($user->id)
        ) {
            abort(403, 'You are not allowed to view this shared account.');
        }

        // Transactions inside this shared account
        $transactions = $sharedAccount->transactions;

        // Total spend
        $totalSpend = $transactions->sum('amount');

        // Your spend
        $yourSpend = $transactions->where('user_id', $user->id)->sum('amount');

        // Member contributions (sorted)
        $memberStats = $transactions
            ->groupBy('user_id')
            ->map(function ($txGroup) use ($totalSpend) {
                $member = $txGroup->first()->user;
                $total = $txGroup->sum('amount');

                return [
                    'user'       => $member,
                    'total'      => $total,
                    'percentage' => $totalSpend > 0
                        ? round(($total / $totalSpend) * 100, 1)
                        : 0,
                ];
            })
            ->sortByDesc('total')
            ->values();

        $membersCount = $sharedAccount->members->count();
        $isCreator = $sharedAccount->creator_user_id === $user->id;

        return view('shareaccounts.show', [
            'sharedAccount' => $sharedAccount,
            'transactions'  => $transactions,
            'memberStats'   => $memberStats,
            'totalSpend'    => $totalSpend,
            'yourSpend'     => $yourSpend,
            'membersCount'  => $membersCount,
            'isCreator'     => $isCreator,
        ]);
    }

    // =========================================================================
    // TRANSACTIONS API FOR MODAL VIEW
    // =========================================================================
    public function transactions(SharedAccount $sharedAccount)
    {
        $user = auth()->user();

        $isCreator = $sharedAccount->creator_user_id === $user->id;
        $isMember  = $sharedAccount->members()->where('user_id', $user->id)->exists();

        if (!$isCreator && !$isMember) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view this account.'
            ], 403);
        }

        $txs = $sharedAccount->transactions()
            ->with(['user:id,name', 'category:id,name'])
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->get();

        $current = $txs->sum('amount');
        $target  = $sharedAccount->target_amount ?? 0;
        $percentage = $target > 0 ? round(($current / $target) * 100) : 0;

        return response()->json([
            'success' => true,
            'account' => [
                'id'             => $sharedAccount->id,
                'name'           => $sharedAccount->name,
                'description'    => $sharedAccount->description,
                'type'           => $sharedAccount->type,
                'target_amount'  => $target,
                'current_amount' => $current,
                'remaining'      => max($target - $current, 0),
                'percentage'     => $percentage,
                'members'        => $sharedAccount->members->map(function ($m) {
                    return $m->name;
                })->values(),
            ],
            'transactions' => $txs->map(function ($tx) {
                return [
                    'description' => $tx->description,
                    'category'    => $tx->category->name ?? 'Uncategorized',
                    'date'        => Carbon::parse($tx->date)->format('d M Y'),
                    'amount'      => $tx->amount,
                ];
            }),
        ]);
    }

    // =========================================================================
    // JSON SHOW FOR EDIT MODAL (NOT DETAIL PAGE)
    // =========================================================================
    public function show(SharedAccount $sharedAccount)
    {
        $user = auth()->user();

        $isCreator = $sharedAccount->creator_user_id === $user->id;
        $isMember  = $sharedAccount->members()->where('user_id', $user->id)->exists();

        if (!$isCreator && !$isMember) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view this account.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'id' => $sharedAccount->id,
            'name' => $sharedAccount->name,
            'description' => $sharedAccount->description,
            'target_amount' => $sharedAccount->target_amount,
            'type' => $sharedAccount->type,
        ]);
    }

    // =========================================================================
    // CREATE SHARED ACCOUNT
    // =========================================================================
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:shared_accounts,name',
            'type' => 'required|in:Expense',
            'limit_or_goal' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $account = SharedAccount::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'type' => $validated['type'],
            'target_amount' => $validated['limit_or_goal'] ?? 0,
            'creator_user_id' => Auth::id(),
        ]);

        // Add creator as first member
        $account->members()->syncWithoutDetaching([Auth::id()]);

        return redirect()->route('sharedaccounts.index')
            ->with('success', 'Shared account created successfully.');
    }

    // =========================================================================
    // UPDATE SHARED ACCOUNT
    // =========================================================================
    public function update(Request $request, SharedAccount $sharedaccount)
    {
        $sharedaccount->refresh();
        $user = auth()->user();

        // Permission: creator or member
        $allowed = $sharedaccount->creator_user_id === $user->id ||
                   $sharedaccount->members->contains($user->id);

        if (!$allowed) {
            return response()->json([
                'success' => false,
                'message' => 'Only members can edit this account.'
            ], 403);
        }

        // Validation
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:shared_accounts,name,' . $sharedaccount->id,
            'description' => 'nullable|string',
            'type' => 'required|in:Expense',
            'target_amount' => 'nullable|numeric|min:0',
        ]);

        $sharedaccount->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Account updated successfully!'
        ]);
    }

    // =========================================================================
    // DELETE SHARED ACCOUNT
    // =========================================================================
    public function destroy(SharedAccount $sharedaccount)
    {
        if ($sharedaccount->creator_user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Only the creator can delete this account.'
            ], 403);
        }

        $keepTransactions = request()->boolean('keep_transactions', true);

        if ($keepTransactions) {
            // Mark as achieved instead of deleting transactions/history.
            $sharedaccount->update([
                'is_achieved' => true,
                'achieved_at' => now(),
            ]);

            $sharedaccount->members()->detach();
            SharedAccountInvite::where('shared_account_id', $sharedaccount->id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Shared account marked as achieved. Transactions kept.'
            ]);
        }

        // Permanent delete: remove transactions + invites + members + account
        $sharedaccount->transactions()->delete();
        $sharedaccount->members()->detach();
        SharedAccountInvite::where('shared_account_id', $sharedaccount->id)->delete();
        $sharedaccount->delete();

        return response()->json([
            'success' => true,
            'message' => 'Shared account and transactions deleted.'
        ]);
    }

    // =========================================================================
    // LEAVE SHARED ACCOUNT (MEMBER ONLY)
    // =========================================================================
    public function leave(SharedAccount $sharedAccount)
    {
        $user = auth()->user();

        if ($sharedAccount->creator_user_id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'The creator cannot leave this account. Please delete it instead.'
            ], 403);
        }

        $isMember = $sharedAccount->members()->where('user_id', $user->id)->exists();
        if (!$isMember) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a member of this account.'
            ], 403);
        }

        $keepTransactions = request()->boolean('keep_transactions', true);
        if (!$keepTransactions) {
            $categoryIds = $sharedAccount->transactions()
                ->where('user_id', $user->id)
                ->pluck('category_id')
                ->unique()
                ->values();

            $sharedAccount->transactions()
                ->where('user_id', $user->id)
                ->delete();

            $this->recalculateSharedAccountTotal($sharedAccount->id);
            $this->recalculateCategoryTotals($categoryIds);
        }

        $sharedAccount->members()->detach($user->id);

        return response()->json([
            'success' => true,
            'message' => $keepTransactions
                ? 'You have left the shared account.'
                : 'You have left the shared account and your transactions were deleted.'
        ]);
    }

    private function recalculateSharedAccountTotal(int $sharedAccountId): void
    {
        $account = SharedAccount::find($sharedAccountId);
        if (!$account) {
            return;
        }

        $total = Transaction::where('shared_account_id', $sharedAccountId)->sum('amount');
        $account->update(['current_total' => $total]);
    }

    private function recalculateCategoryTotals($categoryIds): void
    {
        if (!$categoryIds || $categoryIds->isEmpty()) {
            return;
        }

        foreach ($categoryIds as $categoryId) {
            $spent = Transaction::where('category_id', $categoryId)->sum('amount');
            $budget = optional(\App\Models\Category::find($categoryId))->budget ?? 0;
            $percent = $budget > 0 ? (int) round(($spent / $budget) * 100) : 0;

            \App\Models\Category::where('id', $categoryId)->update([
                'spent' => $spent,
                'percent' => $percent,
            ]);
        }
    }

    // =========================================================================
    // REMOVE MEMBER (CREATOR ONLY)
    // =========================================================================
    public function removeMember(SharedAccount $sharedAccount, User $user)
    {
        $currentUser = auth()->user();

        if ($sharedAccount->creator_user_id !== $currentUser->id) {
            return response()->json([
                'success' => false,
                'message' => 'Only the creator can remove members.'
            ], 403);
        }

        if ($sharedAccount->creator_user_id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'The creator cannot be removed.'
            ], 422);
        }

        $isMember = $sharedAccount->members()->where('user_id', $user->id)->exists();
        if (!$isMember) {
            return response()->json([
                'success' => false,
                'message' => 'User is not a member of this account.'
            ], 404);
        }

        $sharedAccount->members()->detach($user->id);

        return response()->json([
            'success' => true,
            'message' => 'Member removed.'
        ]);
    }

    // =========================================================================
    // INVITE SYSTEM (SEND CODE)
    // =========================================================================
    public function sendInvite(Request $request, SharedAccount $sharedAccount)
    {
        $validated = $request->validate([
            'contact' => 'required|string',
            'type' => 'required|string',
        ]);

        $contact = trim($validated['contact']);
        $platform = strtolower($validated['type']);

        // Auto-detect email
        if (filter_var($contact, FILTER_VALIDATE_EMAIL)) {
            $platform = 'email';
        }

        if (!in_array($platform, ['email', 'whatsapp', 'telegram'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid platform type.',
            ], 422);
        }

        $code = random_int(100000, 999999);
        $expiresAt = now()->addMinutes(10);

        $invite = SharedAccountInvite::updateOrCreate(
            [
                'shared_account_id' => $sharedAccount->id,
                'recipient_contact' => $contact,
            ],
            [
                'contact_type' => $platform,
                'auth_code' => $code,
                'status' => 'pending',
                'expires_at' => $expiresAt,
            ]
        );

        // Email send
        if ($platform === 'email') {
            try {
                $u = User::where('email', $contact)->first();

                if ($u) {
                    $u->notify(new InviteNotification($invite));
                } else {
                    \Notification::route('mail', $contact)
                        ->notify(new InviteNotification($invite));
                }
            } catch (\Exception $e) {
                \Log::error('Invite email error: ' . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Verification code sent!',
        ]);
    }

    // =========================================================================
    // DELETE INVITE (recipient-side)
    // =========================================================================
    public function deleteInvite(SharedAccountInvite $invite)
    {
        $user = auth()->user();
        if (
            $invite->recipient_contact !== $user->email &&
            $invite->recipient_contact !== $user->phone_number
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this invite.',
            ], 403);
        }

        $invite->delete();

        return response()->json([
            'success' => true,
            'message' => 'Invite deleted.',
        ]);
    }

    // =========================================================================
    // VERIFY INVITE
    // =========================================================================
    public function verifyInvite(Request $request, SharedAccount $sharedAccount)
    {
        $validated = $request->validate([
            'contact' => 'required|string',
            'code' => 'required|string|size:6',
        ]);

        $invite = SharedAccountInvite::where('shared_account_id', $sharedAccount->id)
            ->where('recipient_contact', $validated['contact'])
            ->where('auth_code', $validated['code'])
            ->where('status', 'pending')
            ->first();

        if ($invite && now()->greaterThan($invite->expires_at)) {
            return response()->json(['success' => false, 'message' => 'Code expired.']);
        }

        if (!$invite) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired code.'
            ], 401);
        }

        // Find or create user
        $user = User::where('email', $validated['contact'])
            ->orWhere('phone_number', $validated['contact'])
            ->first();

        if (!$user) {
            $user = User::create([
                'name' => 'Invited Member',
                'email' => filter_var($validated['contact'], FILTER_VALIDATE_EMAIL)
                    ? $validated['contact']
                    : null,
                'phone_number' => preg_match('/^[0-9\+\-\s]+$/', $validated['contact'])
                    ? $validated['contact']
                    : null,
                'password' => bcrypt('temporary123'),
            ]);
        }

        $alreadyMember = $sharedAccount->members()->where('user_id', $user->id)->exists();

        // Attach user to account
        $sharedAccount->members()->syncWithoutDetaching([$user->id]);

        // Mark invite as used
        $invite->update(['status' => 'verified']);

        if (!$alreadyMember) {
            $creator = User::find($sharedAccount->creator_user_id);
            if ($creator && $creator->id !== $user->id) {
                $joinNotifyEnabled = property_exists($creator, 'notify_shared_join')
                    ? ($creator->notify_shared_join ?? true)
                    : true;

                if ($joinNotifyEnabled) {
                    $creator->notify(new JoinedNotification(
                        $sharedAccount->name ?? 'Shared Account',
                        $user->name ?? 'New member',
                        $sharedAccount->id
                    ));
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Member verified and added!',
        ]);
    }

    // =========================================================================
    // JOIN BY CODE
    // =========================================================================
    public function joinByCode(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $invite = SharedAccountInvite::where('auth_code', $validated['code'])
            ->where('status', 'pending')
            ->orderByDesc('expires_at')
            ->first();

        if (!$invite) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired code.',
            ], 404);
        }

        if ($invite->expires_at && now()->greaterThan($invite->expires_at)) {
            $invite->update(['status' => 'expired']);

            return response()->json([
                'success' => false,
                'message' => 'Code expired. Request new invite.',
            ]);
        }

        $account = SharedAccount::find($invite->shared_account_id);

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found.',
            ], 404);
        }

        $user = Auth::user();
        $alreadyMember = $account->members()->where('user_id', $user->id)->exists();

        // Attach user
        $account->members()->syncWithoutDetaching([$user->id]);

        // Verify invite
        $invite->update([
            'status' => 'verified',
            'recipient_contact' =>
                $invite->recipient_contact ?: ($user->email ?? $user->phone_number),
        ]);

        if (!$alreadyMember) {
            $creator = User::find($account->creator_user_id);
            if ($creator && $creator->id !== $user->id) {
                $joinNotifyEnabled = property_exists($creator, 'notify_shared_join')
                    ? ($creator->notify_shared_join ?? true)
                    : true;

                if ($joinNotifyEnabled) {
                    $creator->notify(new JoinedNotification(
                        $account->name ?? 'Shared Account',
                        $user->name ?? 'New member',
                        $account->id
                    ));
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Joined shared account.',
            'account' => [
                'id'   => $account->id,
                'name' => $account->name,
            ],
        ]);
    }

    // =========================================================================
    // UPDATE PERSONAL BUDGET
    // =========================================================================
    public function updatePersonal(Request $request)
    {
        $validated = $request->validate([
            'target_amount' => 'required|numeric|min:0',
        ]);

        $user = Auth::user();
        $user->update([
            'personal_target_amount' => $validated['target_amount'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Personal budget updated.',
            'target_amount' => $user->personal_target_amount,
        ]);
    }
}
