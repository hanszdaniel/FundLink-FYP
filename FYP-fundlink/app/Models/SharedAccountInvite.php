<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SharedAccountInvite extends Model
{
    protected $table = 'shared_account_invites';

    protected $fillable = [
        'shared_account_id',
        'recipient_contact',
        'contact_type',
        'auth_code',
        'token',
        'expires_at',
        'status',
    ];

    protected $casts = [
        'expires_at' => 'datetime'
    ];

    public function sharedAccount()
    {
        return $this->belongsTo(SharedAccount::class);
    }
}
