<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
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
             * The task title. Required non-empty string with maximum 255 characters.
             *
             * @example Buy groceries and cook dinner
             */
            'title' => ['required', 'string', 'max:255'],

            /**
             * Optional task description providing additional details. Maximum 1000 characters.
             *
             * @example Remember to check for fresh produce and get organic vegetables
             */
            'description' => ['nullable', 'string', 'max:1000'],

            /**
             * Task status. Must be one of: pending, in_progress, completed. Defaults to 'pending' if not provided.
             *
             * @example pending
             */
            'status' => ['nullable', 'string', Rule::in([
                Task::STATUS_PENDING,
                Task::STATUS_IN_PROGRESS,
                Task::STATUS_COMPLETED,
            ])],
        ];
    }
}
