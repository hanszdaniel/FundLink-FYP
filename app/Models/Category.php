<?php

// app/Models/Category.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    // 🎯 CRUCIAL FIX: Inform Eloquent that the primary key is a string, not an integer.
    protected $keyType = 'string';
    
    // Also, tell Eloquent not to auto-increment the ID, as you are setting it manually (slug-based)
    public $incrementing = false; 

    protected $fillable = [
        'id', // Since you are setting this manually
        'user_id',
        'name',
        'icon',
        'budget',
        'description',
        'spent',
        'percent',
        'color',
    ];

    /**
     * A Category can have many Transactions.
     */
    public function transactions()
    {
        // Links back to the 'category_id' in the transactions table
        return $this->hasMany(Transaction::class, 'category_id');
    }
}
