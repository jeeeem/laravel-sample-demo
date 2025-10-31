<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Anyone can register
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
             * User's full name. Must contain only letters, spaces, hyphens, dots, and apostrophes.
             * Minimum 2 characters, maximum 255 characters.
             *
             * @example John Doe
             */
            'name' => ['required', 'string', 'min:2', 'max:255', 'regex:/^[\p{L}\s\-\.\']+$/u'],

            /**
             * User's email address. Must be unique in the system and a valid format (RFC compliant with DNS check).
             * Maximum 255 characters.
             *
             * @example john.doe@example.com
             */
            'email' => ['required', 'string', 'email:rfc,dns', 'max:255', 'unique:users'],

            /**
             * User's password. Must be at least 8 characters, maximum 72 characters.
             * Requires a matching password_confirmation field to be sent alongside.
             *
             * @example SecurePass123!
             */
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
        ];
    }
}
