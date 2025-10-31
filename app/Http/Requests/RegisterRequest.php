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
             * User's full name containing only letters, spaces, hyphens, dots, and apostrophes with minimum 2 and maximum 255 characters.
             *
             * @example John Doe
             */
            'name' => ['required', 'string', 'min:2', 'max:255', 'regex:/^[\p{L}\s\-\.\']+$/u'],

            /**
             * User's email address with RFC compliant format and DNS check, must be unique in the system with maximum 255 characters.
             *
             * @example john.doe@example.com
             */
            'email' => ['required', 'string', 'email:rfc,dns', 'max:255', 'unique:users'],

            /**
             * User's password with minimum 8 and maximum 72 characters, requires a matching password_confirmation field.
             *
             * @example SecurePass123!
             */
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
        ];
    }
}
