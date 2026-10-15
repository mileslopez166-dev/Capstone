<?php

namespace App\Http\Requests;

use App\Models\ProfileChangeRequest;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        $emailRules = ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($this->user()->id)];

        if ($this->user()->isStudent() || $this->user()->isTeacher()) {
            $pendingId = ProfileChangeRequest::query()
                ->pending()
                ->where('user_id', $this->user()->id)
                ->value('id');
            $emailRules[] = Rule::unique(ProfileChangeRequest::class, 'requested_email')
                ->where(fn ($query) => $query->where('status', 'pending'))
                ->ignore($pendingId);
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => $emailRules,
            'gender' => ['nullable', 'string', Rule::in(['male', 'female'])],
        ];
    }
}
