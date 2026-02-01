<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendInviteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // allow all authenticated users
    }

    public function rules(): array
    {
        return [
            'recipient_contact' => 'required|string',
            'contact_type'      => 'required|in:email,whatsapp',
        ];
    }

    public function messages(): array
    {
        return [
            'recipient_contact.required' => 'Please enter the email or phone number.',
            'contact_type.required'      => 'Invite type is required.',
            'contact_type.in'            => 'Invite type must be email or whatsapp.',
        ];
    }
}
