<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Anyone can attempt to login
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            /**
             * User's registered email address.
             *
             * @example john.doe@example.com
             */
            'email' => ['required', 'string', 'email'],

            /**
             * User's account password.
             *
             * @example SecurePass123!
             */
            'password' => ['required', 'string'],
        ];
    }
}
