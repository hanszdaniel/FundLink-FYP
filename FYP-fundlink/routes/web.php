<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\SharedAccountController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;

/*
|--------------------------------------------------------------------------
| TEST EMAIL ROUTE (Debug Only)
|--------------------------------------------------------------------------
*/
Route::get('/test-email', function () {
    $to = 'hanszboy14@gmail.com';

    Mail::raw('This is a test email from Fundlink.', function ($message) use ($to) {
        $message->to($to)->subject('Fundlink Test Email');
    });

    return "Test email sent to: " . $to;
})->name('test.email');

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/

// Landing
Route::get('/', fn () => view('welcome'));

// Auth
Route::get('/login', fn () => view('login'))->name('login');
Route::post('/login', [AuthController::class, 'login']);

Route::get('/signup', fn () => view('signup'))->name('register');
Route::post('/signup', [AuthController::class, 'register']);

// Email verification
Route::get('/email/verify/{token}', [AuthController::class, 'verifyEmail'])
    ->name('auth.verify');

/*
|--------------------------------------------------------------------------
| PASSWORD RESET
|--------------------------------------------------------------------------
*/

Route::get('/forgot-password', fn () => view('auth.forgot-password'))
    ->name('password.request');

// Allow password reset link to be requested even if user is logged in (avoids 302 redirect to dashboard).
Route::post('/forgot-password/send', [PasswordResetLinkController::class, 'store'])
    ->name('password.email.custom');

Route::get('/reset-password/{token}', fn ($token) => view('auth.reset-password', ['token' => $token]))
    ->name('password.reset');

Route::post('/reset-password', [NewPasswordController::class, 'store'])
    ->name('password.update');

/*
|--------------------------------------------------------------------------
| AUTHENTICATED ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'nocache'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */

    // Mark ONE notification as read
    Route::post('/notifications/{id}/read', function ($id) {
        auth()->user()
            ->notifications()
            ->where('id', $id)
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    })->name('notifications.read');

    // Mark ALL notifications as read
    Route::post('/notifications/read-all', function () {
        auth()->user()->unreadNotifications->markAsRead();
        return response()->json(['success' => true]);
    })->name('notifications.readAll');

    // Delete ONE notification
    Route::delete('/notifications/{id}', function ($id) {
        $deleted = auth()->user()
            ->notifications()
            ->where('id', $id)
            ->delete();

        return response()->json(['success' => (bool) $deleted]);
    })->name('notifications.delete');

    // Delete ALL notifications
    Route::delete('/notifications', function () {
        auth()->user()->notifications()->delete();
        return response()->json(['success' => true]);
    })->name('notifications.clear');

    Route::post('/profile/notifications/update', function (\Illuminate\Http\Request $request) {
    $user = auth()->user();

    $allowed = [
        'notify_budget_near',
        'notify_budget_over',
        'notify_category_near',
        'notify_category_over',
        'notify_shared_join',
        'notify_shared_near',
        'notify_shared_over',
        'summary_frequency',
    ];

    if (in_array($request->field, $allowed)) {
        $user->update([
            $request->field => $request->value
        ]);
    }

    return response()->json(['success' => true]);
})->middleware('auth');


    /*
    |--------------------------------------------------------------------------
    | Profile
    |--------------------------------------------------------------------------
    */
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Password change with OTP
    Route::post('/profile/password/send-otp', [ProfileController::class, 'sendPasswordOtp'])
        ->name('profile.password.otp');

    Route::post('/profile/password/verify-otp', [ProfileController::class, 'verifyPasswordOtp'])
        ->name('profile.password.verify');

    Route::post('/profile/password/update', [ProfileController::class, 'updatePassword'])
        ->name('profile.password.update');

    /*
    |--------------------------------------------------------------------------
    | Transactions
    |--------------------------------------------------------------------------
    */
    Route::get('/transaction', [TransactionController::class, 'index'])
        ->name('transaction');

    Route::post('/transaction', [TransactionController::class, 'store'])
        ->name('transactions.store');

    Route::put('/transaction/{transaction}', [TransactionController::class, 'update'])
        ->name('transactions.update');

    Route::delete('/transaction/{transaction}', [TransactionController::class, 'destroy'])
        ->name('transactions.destroy');

    // Personal transactions (AJAX)
    Route::get('/personal/transactions', [TransactionController::class, 'personalTransactions'])
        ->name('personal.transactions');

    // PDF Statement
    Route::get('/profile/statements/export', [TransactionController::class, 'export'])
        ->name('profile.statements.export');

    /*
    |--------------------------------------------------------------------------
    | Categories
    |--------------------------------------------------------------------------
    */
    Route::get('/categories', fn () => view('category'))->name('categories');

    Route::get('/categories/data', [CategoryController::class, 'index'])
        ->name('categories.data');

    Route::get('/categories/{id}/transactions', [CategoryController::class, 'transactions'])
        ->name('categories.transactions');

    Route::post('/categories', [CategoryController::class, 'store'])
        ->name('categories.store');

    Route::patch('/categories/{id}', [CategoryController::class, 'update'])
        ->name('categories.update');

    Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])
        ->name('categories.destroy');

    /*
    |--------------------------------------------------------------------------
    | Shared Accounts
    |--------------------------------------------------------------------------
    */
    Route::get('/sharedaccount', [SharedAccountController::class, 'index'])
        ->name('sharedaccounts.index');

    Route::post('/sharedaccount', [SharedAccountController::class, 'store'])
        ->name('sharedaccounts.store');

    // Detail page (full view)
    Route::get('/sharedaccount/detail/{id}', [SharedAccountController::class, 'detail'])
        ->name('sharedaccounts.detail');
    // JSON for modals
    Route::get('/sharedaccount/view/{sharedAccount}', [SharedAccountController::class, 'show'])
        ->name('sharedaccounts.view');

    Route::get('/sharedaccount/{sharedAccount}/transactions',
        [SharedAccountController::class, 'transactions'])
        ->name('sharedaccounts.transactions');

    Route::post('/sharedaccount/{sharedAccount}/invite/send',
        [SharedAccountController::class, 'sendInvite'])
        ->name('sharedaccounts.invite.send');

    Route::post('/sharedaccount/{sharedAccount}/invite/verify',
        [SharedAccountController::class, 'verifyInvite'])
        ->name('sharedaccounts.invite.verify');

    Route::delete('/sharedaccount/invite/{invite}',
        [SharedAccountController::class, 'deleteInvite'])
        ->name('sharedaccounts.invite.delete');

    Route::post('/sharedaccount/join',
        [SharedAccountController::class, 'joinByCode'])
        ->name('sharedaccounts.join');

    Route::delete('/sharedaccount/{sharedAccount}/leave',
        [SharedAccountController::class, 'leave'])
        ->name('sharedaccounts.leave');

    Route::delete('/sharedaccount/{sharedAccount}/members/{user}',
        [SharedAccountController::class, 'removeMember'])
        ->name('sharedaccounts.members.remove');

    Route::post('/sharedaccount/personal',
        [SharedAccountController::class, 'updatePersonal'])
        ->name('sharedaccounts.personal');

    Route::resource('sharedaccount', SharedAccountController::class)
        ->except(['create', 'show', 'edit', 'index', 'store']);

    /*
    |--------------------------------------------------------------------------
    | Logout
    |--------------------------------------------------------------------------
    */
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');
});
