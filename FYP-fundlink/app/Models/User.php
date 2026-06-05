<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany; // <--- NEW IMPORT
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str; 
use App\Models\SharedAccount; // <--- Ensure SharedAccount Model is imported
use App\Models\Transaction; // <--- Ensure Transaction Model is imported (if you plan to use this relation)

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone_number',
        'password',
        'avatar',
        'personal_target_amount',
        // Notification preferences
        'notify_budget_near',
        'notify_budget_over',
        'notify_category_near',
        'notify_category_over',
        'notify_shared_join',
        'notify_shared_near',
        'notify_shared_over',
        'summary_frequency',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            // Notification preferences
            'notify_budget_near' => 'boolean',
            'notify_budget_over' => 'boolean',
            'notify_category_near' => 'boolean',
            'notify_category_over' => 'boolean',
            'notify_shared_join' => 'boolean',
            'notify_shared_near' => 'boolean',
            'notify_shared_over' => 'boolean',
        ];
    }
    
    // ----------------------------------------------------
    // --- AVATAR LOGIC (Accessor/Mutator) ---
    // ----------------------------------------------------

    /**
     * Accessor to get the full URL of the user's avatar or a dynamic placeholder.
     *
     * @return Attribute
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(function (): string {
            // Check for real avatar first
            if ($this->avatar && Storage::disk('public')->exists($this->avatar)) {
                return Storage::disk('public')->url($this->avatar);
            }

            // --- Fallback: Custom Initials Generator ---
            $name = (string)($this->attributes['name'] ?? 'U'); 
            
            if (empty(trim($name))) {
                $name = 'U';
            }

            $parts = explode(' ', trim($name));
            $initials = '';
            
            if (count($parts) === 1) {
                $initials = strtoupper(substr($parts[0], 0, 2));
            } elseif (count($parts) >= 2) {
                $initials = strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
            } else {
                $initials = 'U';
            }
            // Note: The custom initials logic appears correct and handles the fallback without requiring Str::initials().

            $urlSafeName = urlencode($initials);
            
            // Generates a simple blue circular image with the user's initials
            return "https://placehold.co/120x120/8c98e8/ffffff?text={$urlSafeName}&font=roboto&fontsize=50";
        });
    }
    
    // ----------------------------------------------------
    // --- RELATIONSHIPS ---
    // ----------------------------------------------------
    
    /**
     * Get all transactions made by the User.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'user_id');
    }

    /**
     * Get all shared accounts created by the User (One-to-Many).
     * We renamed this for clarity to distinguish it from the membership relationship.
     */
    public function createdAccounts(): HasMany // <--- RENAMED
    {
        // Assuming the foreign key in shared_accounts is 'creator_user_id'
        return $this->hasMany(SharedAccount::class, 'creator_user_id');
    }

    /**
     * Get all shared accounts the User is a member of (Many-to-Many).
     * This is the essential relationship for displaying the dashboard cards.
     * * @return BelongsToMany
     */
    public function sharedAccounts(): BelongsToMany // <--- THE CRITICAL CHANGE
    {
        // 1. Target Model: SharedAccount::class
        // 2. Pivot Table: 'shared_account_members' (defined in your migration)
        // 3. Foreign Key on Pivot (this model's ID): 'user_id'
        // 4. Related Key on Pivot (target model's ID): 'shared_account_id'
        return $this->belongsToMany(
            SharedAccount::class, 
            'shared_account_members', 
            'user_id', 
            'shared_account_id'
        )->withTimestamps();
    }
}
