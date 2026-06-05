<?php

namespace App\Http\Controllers;

use App\Models\SharedAccountInvite;
use App\Models\SharedAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Show the user's profile page.
     */
    public function index()
    {
        // Get the currently authenticated user
        $user = Auth::user();

        // Use all-time data to avoid empty summaries when current month has no transactions
        $monthlyExpenses = $user->transactions()->sum('amount');

        $monthlyTransactionCount = $user->transactions()->count();

        $averageTransaction = $monthlyTransactionCount > 0
            ? $monthlyExpenses / $monthlyTransactionCount
            : 0;

        // --- Biggest category (all time) ---
        $topCategory = $user->transactions()
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->first();

        $topCategoryName = $topCategory ? optional($topCategory->category)->name ?? 'Uncategorized' : 'No Data';
        $topCategoryValue = $topCategory ? $topCategory->total : 0;

        // --- Last 30 days trend ---
        $dailyTrend = $user->transactions()
            ->selectRaw('DATE(date) as day, SUM(amount) as total')
            ->whereDate('date', '>=', now()->subDays(29)->startOfDay())
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        // --- Category Breakdown (all time) ---
        $categoryBreakdown = $user->transactions()
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->get();

        // --- Latest 5 transactions (all time) ---
        $latestTransactions = $user->transactions()
            ->with('category')
            ->orderByDesc('date')
            ->take(5)
            ->get();

        // --- Shared accounts overview ---
        $sharedAccounts = $user->sharedAccounts()
            ->where('shared_accounts.is_achieved', false)
            ->withCount('members')
            ->withSum('transactions', 'amount')
            ->get();

        $achievedSharedAccounts = SharedAccount::query()
            ->where('is_achieved', true)
            ->where(function ($query) use ($user) {
                $query->where('creator_user_id', $user->id)
                    ->orWhereHas('members', function ($memberQuery) use ($user) {
                        $memberQuery->where('users.id', $user->id);
                    });
            })
            ->withCount('members')
            ->withSum('transactions', 'amount')
            ->get();

        // Pending invites for this user (match email or phone)
        $pendingInvites = SharedAccountInvite::with('sharedAccount:id,name')
            ->where('status', 'pending')
            ->where(function ($q) use ($user) {
                $q->where('recipient_contact', $user->email);
                if ($user->phone_number) {
                    $q->orWhere('recipient_contact', $user->phone_number);
                }
            })
            ->orderByDesc('created_at')
            ->get();

        $accountsJoined = $sharedAccounts->count();
        $totalMembers   = $sharedAccounts->sum('members_count');
        $sharedSpend    = $sharedAccounts->sum('transactions_sum_amount');

        return view('profile', [
            'user' => $user,
            'monthlyExpenses' => $monthlyExpenses,
            'monthlyTransactionCount' => $monthlyTransactionCount,
            'averageTransaction' => $averageTransaction,
            'topCategoryName' => $topCategoryName,
            'topCategoryValue' => $topCategoryValue,
            'dailyTrend' => $dailyTrend,
            'categoryBreakdown' => $categoryBreakdown,
            'latestTransactions' => $latestTransactions,
            'sharedAccounts' => $sharedAccounts,
            'achievedSharedAccounts' => $achievedSharedAccounts,
            'accountsJoined' => $accountsJoined,
            'totalMembers' => $totalMembers,
            'sharedSpend' => $sharedSpend,
            'pendingInvites' => $pendingInvites,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request)
    {
        // Get the authenticated user
        $user = Auth::user();

        // Base validation rules
        $rules = [
            'name' => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            // Make sure the email is unique, BUT ignore the current user's own email
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'avatar' => 'nullable|image|max:2048',
        ];

        // If attempting to change password, require current + confirmation
        if ($request->filled('password') || $request->filled('current_password')) {
            $rules['current_password'] = ['required'];
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        }

        $validatedData = $request->validate($rules);

        // Update profile info
        $user->fill([
            'name' => $validatedData['name'],
            'phone_number' => $validatedData['phone_number'] ?? null,
            'email' => $validatedData['email'],
        ]);

        // Handle avatar upload (if provided)
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path;
        }

        // Update password when provided and current password matches
        if ($request->filled('password')) {
            if (!Hash::check($request->input('current_password'), $user->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The current password is incorrect.',
                ], 422);
            }

            $user->password = Hash::make($request->input('password'));
        }

        $user->save();


        // Return a success response
        return response()->json([
            'status' => 'success',
            'message' => $request->filled('password')
                ? 'Profile and password updated successfully!'
                : 'Profile updated successfully!',
            // Send back the updated user data
            'user' => $user->only('name', 'email', 'phone_number') + [
                'avatar_url' => $user->avatar_url,
            ],
        ]);
    }
    public function summary()
{
    $user = auth()->user();
    $now = now();

    // --- Monthly totals ---
    $monthlyExpenses = $user->transactions()
        ->whereMonth('date', $now->month)
        ->whereYear('date', $now->year)
        ->sum('amount');

    $monthlyTransactionCount = $user->transactions()
        ->whereMonth('date', $now->month)
        ->whereYear('date', $now->year)
        ->count();

    $averageTransaction = $monthlyTransactionCount > 0
        ? $monthlyExpenses / $monthlyTransactionCount
        : 0;

    // --- Biggest category ---
    $topCategory = $user->transactions()
        ->selectRaw('category_id, SUM(amount) as total')
        ->whereMonth('date', $now->month)
        ->whereYear('date', $now->year)
        ->groupBy('category_id')
        ->orderByDesc('total')
        ->first();

    $topCategoryName = $topCategory ? $topCategory->category->name : 'No Data';
    $topCategoryValue = $topCategory ? $topCategory->total : 0;

    // --- Daily trend for line chart ---
    $dailyTrend = $user->transactions()
        ->selectRaw('DATE(date) as day, SUM(amount) as total')
        ->whereMonth('date', $now->month)
        ->whereYear('date', $now->year)
        ->groupBy('day')
        ->orderBy('day')
        ->get();

    // --- Category Breakdown ---
    $categoryBreakdown = $user->transactions()
        ->selectRaw('category_id, SUM(amount) as total')
        ->whereMonth('date', $now->month)
        ->whereYear('date', $now->year)
        ->groupBy('category_id')
        ->get();

    // --- Latest 5 transactions ---
    $latestTransactions = $user->transactions()
        ->with('category')
        ->orderByDesc('date')
        ->take(5)
        ->get();

    return view('profile.summary', [
        'monthlyExpenses' => $monthlyExpenses,
        'monthlyTransactionCount' => $monthlyTransactionCount,
        'averageTransaction' => $averageTransaction,
        'topCategoryName' => $topCategoryName,
        'topCategoryValue' => $topCategoryValue,
        'dailyTrend' => $dailyTrend,
        'categoryBreakdown' => $categoryBreakdown,
        'latestTransactions' => $latestTransactions,
    ]);
}

}
