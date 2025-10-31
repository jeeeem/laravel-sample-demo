<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled by TaskPolicy
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
             * The task title. Only validated if provided in request. Must be a non-empty string with maximum 255 characters.
             * Omit this field entirely to leave the current title unchanged.
             *
             * @example Complete weekly report and submit to manager
             */
            'title' => ['sometimes', 'required', 'string', 'max:255'],

            /**
             * Optional task description. Set to null to clear existing description. Maximum 1000 characters if provided.
             *
             * @example Finish by Friday EOD and include all metrics from Q4
             */
            'description' => ['nullable', 'string', 'max:1000'],

            /**
             * Task status. Only validated if provided. Must be one of: pending, in_progress, completed.
             * Omit this field to leave the current status unchanged.
             *
             * @example in_progress
             */
            'status' => ['sometimes', 'string', Rule::in(TaskStatus::values())],
        ];
    }
}
