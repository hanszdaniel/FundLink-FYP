<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category; // dY`^ 1. ADD THIS LINE (The Model for your database table)
use Illuminate\Support\Str; // dY`^ 2. ADD THIS LINE (Used for generating unique IDs)
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource (GET /api/categories).
     */
    public function index()
    {
        try {
            $user = Auth::user();
            $userId = $user->id;
            $sharedAccountIds = $this->getSharedAccountIds($user);

            // Fetch only needed columns and sum only this user's transactions to keep the query light
            $categories = Category::select(['id', 'name', 'icon', 'budget', 'description', 'color'])
                ->where('name', '!=', 'Uncategorized')
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
                ->withSum(['transactions as calc_spent' => function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                }], 'amount')
                ->orderBy('name')
                ->get();

            $categories->transform(function ($category) {
                $spent = $category->calc_spent ?? 0;
                $budget = $category->budget ?? 0;

                $category->spent = $spent;
                $category->percent = $budget > 0 ? (int) round(($spent / $budget) * 100) : 0;

                // Drop helper attribute so the frontend receives the expected shape
                unset($category->calc_spent);
                return $category;
            });

            return response()->json($categories);
        } catch (\Exception $e) {
            // In case of a database error
            return response()->json(['message' => 'Failed to retrieve categories.'], 500);
        }
    }

    /**
     * Store a newly created resource in storage (POST /api/categories).
     */
    public function store(Request $request)
    {
        $userId = Auth::id();

        // 1. Validation
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->where(function ($query) use ($userId) {
                    return $query->where('user_id', $userId);
                }),
            ],
            'budget' => 'required|numeric|min:0',
            'icon' => 'required|string',
        ]);
        
        // Helper: Generate a random color for the card
        $randomColor = '#'.substr(md5(mt_rand()), 0, 6);

        // 2. Create the Category in the database
        try {
            $category = Category::create([
                // Generate a simple unique ID (as used in the JS mock data structure)
                'id' => Str::slug($request->input('name')) . '-' . time(), 
                'user_id' => $userId,
                'name' => $request->input('name'),
                'icon' => $request->input('icon'),
                'budget' => $request->input('budget'),
                'description' => $request->input('description') ?? '', // Use empty string if description is null
                'spent' => 0.00, // Initialize spent value
                'percent' => 0,  // Initialize percentage
                'color' => $randomColor, 
            ]);

            // 3. Return the newly created category as a JSON response (Status 201: Created)
            return response()->json($category, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to save category. Database error.'], 500);
        }
    }

    /**
     * Display the specified resource. (Not needed for current task, leave empty or delete)
     */
    public function show(string $id)
    {
        // ...
    }

    /**
     * Return category details with all its transactions (for the View modal).
     */
    public function transactions(string $id)
    {
        $user = Auth::user();
        $userId = $user->id;
        $sharedAccountIds = $this->getSharedAccountIds($user);

        $category = Category::where('id', 'LIKE', '%' . $id)
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
            ->first();
        if (!$category) {
            return response()->json(['success' => false, 'message' => 'Category not found.'], 404);
        }

        $txs = Transaction::where('user_id', $userId)
            ->where('category_id', $category->id)
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->with(['category', 'sharedAccount'])
            ->get()
            ->map(function ($tx) {
                return [
                    'id' => $tx->id,
                    'description' => $tx->description,
                    'amount' => (float) $tx->amount,
                    'date' => $tx->date ? Carbon::parse($tx->date)->format('Y-m-d') : null,
                    'time' => $tx->created_at ? $tx->created_at->format('H:i') : null,
                    'category' => $tx->category->name ?? 'Uncategorized',
                    'category_icon' => $tx->category->icon ?? null,
                    'account' => $tx->sharedAccount->name ?? 'Personal',
                ];
            });

        $spent = $txs->sum('amount');
        $budget = $category->budget ?? 0;
        $remaining = max($budget - $spent, 0);
        $percentage = $budget > 0 ? round(($spent / $budget) * 100, 2) : 0;

        return response()->json([
            'success' => true,
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'icon' => $category->icon,
                'budget' => (float) $budget,
                'spent' => $spent,
                'remaining' => $remaining,
                'percent' => $percentage,
            ],
            'transactions' => $txs,
        ]);
    }

    /**
     * Update the specified resource in storage (PATCH /api/categories/{id}).
     * NOTE: We change the type hint from Category to string $id to handle OLD string IDs
     */
    public function update(Request $request, string $id)
    {
        try {
            // This handles both:
            // A) New numeric IDs (e.g., ID 5 will match WHERE id LIKE '%5')
            // B) Old string IDs where the JS only sends the number part (e.g., 'gym-176...06' will match WHERE id LIKE '%176...06')
            $category = Category::where('id', 'LIKE', '%' . $id)
                ->where('user_id', Auth::id())
                ->first();

            if (!$category) {
                // If the category still isn't found, return 404
                return response()->json(['message' => 'Category not found for update.'], 404);
            }

            // 1. Validation
            $validatedData = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('categories', 'name')
                        ->where(fn ($query) => $query->where('user_id', Auth::id()))
                        ->ignore($category->id, 'id'),
                ],
                'icon' => 'required|string|max:255',
                'budget' => 'nullable|numeric|min:0',
                'description' => 'nullable|string',
            ]);
            
            // 3. Update the model with validated data
            $category->update($validatedData);

            // 4. Return the updated category instance as JSON to the frontend
            return response()->json($category);

        } catch (\Exception $e) {
            // Return a detailed error if update fails
            return response()->json(['message' => 'Failed to update category: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * 1. Change type hint from Category to string $id to get the ID directly
     */
    public function destroy(string $id) 
    {
        try {
            // SEARCH the database using the raw ID string
            // Since we can't reliably reconstruct the name part, we will use a WHERE clause
            // to search the 'id' column for anything that *contains* the number sent by the JS.
            $category = Category::where('id', 'LIKE', '%' . $id)
                ->where('user_id', Auth::id())
                ->first();

            if (!$category) {
                return response()->json(['message' => 'Category not found.'], 404);
            }

            // Guard: keep the fallback category for reassignment
            if ($category->id === 'uncategorized' || $category->name === 'Uncategorized') {
                return response()->json([
                    'message' => 'The Uncategorized category cannot be deleted.',
                ], 422);
            }

            $uncategorized = Category::where('name', 'Uncategorized')->first();
            if (!$uncategorized) {
                $uncategorized = Category::create([
                    'id' => 'uncategorized',
                    'user_id' => null,
                    'name' => 'Uncategorized',
                    'icon' => 'fas fa-question-circle',
                    'budget' => 0,
                    'description' => 'Default category for uncategorized transactions.',
                    'spent' => 0.00,
                    'percent' => 0,
                    'color' => '#6B7280',
                ]);
            }

            // Reassign any transactions linked to this category before deletion
            Transaction::where('category_id', $category->id)
                ->update(['category_id' => $uncategorized->id]);

            // Execute the deletion command
            $category->delete();

            // Return a 204 No Content status. 
            return response()->json(null, 204); 
            
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete category.'], 500);
        }
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
}
