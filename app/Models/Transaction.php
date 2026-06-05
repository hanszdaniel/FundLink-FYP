<?php
// app/Models/Transaction.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'description',
        'amount',
        'date',
        'category_id',
        'shared_account_id',
        'receipt_image_path',
    ];

    /**
     * A Transaction belongs to one Category.
     */
    public function category(): BelongsTo
    {
        // Because your Category primary key is a STRING and its foreign key
        // is 'category_id', we must explicitly define the foreign key and 
        // the local key (the category's primary key, which is 'id').
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    /**
     * A Transaction optionally belongs to a SharedAccount.
     * This relationship will return null if shared_account_id is null (personal transaction).
     */
    public function sharedAccount(): BelongsTo
    {
        // Since shared_account_id uses a standard integer foreign key 
        // linked to the SharedAccount 'id' primary key, the standard definition works.
        return $this->belongsTo(SharedAccount::class);
    }
    
    /**
     * A Transaction belongs to the User who created it.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
