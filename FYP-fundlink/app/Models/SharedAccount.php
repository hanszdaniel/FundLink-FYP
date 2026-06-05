<?php

namespace App\Models;

use App\Models\User;
use App\Models\SharedAccountInvite;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SharedAccount extends Model
{
    protected $fillable = [
        'name',
        'description',     
        'type',             
        'target_amount',
        'creator_user_id',
        'is_achieved',
        'achieved_at',
    ];

    protected $casts = [
        'achieved_at' => 'datetime',
        'is_achieved' => 'boolean',
    ];

    /**
     * Get all transactions associated with this Shared Account.
     */
    public function transactions(): HasMany
    {
        // Links to the 'shared_account_id' column in the transactions table
        return $this->hasMany(Transaction::class); 
    }

    // You would add relationships here for members later (many-to-many)
    public function members(): BelongsToMany
{
    return $this->belongsToMany(User::class, 'shared_account_members', 'shared_account_id', 'user_id');
}

public function invites(): HasMany
{
    return $this->hasMany(SharedAccountInvite::class);
}
}
