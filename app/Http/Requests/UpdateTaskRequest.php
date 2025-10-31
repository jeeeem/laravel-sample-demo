<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Task;
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
             * The task title. Only required if provided.
             *
             * @example Complete weekly report
             */
            'title' => ['sometimes', 'required', 'string', 'max:255'],

            /**
             * Optional task description. Can be set to null to clear existing description.
             *
             * @example Finish by Friday EOD
             */
            'description' => ['nullable', 'string', 'max:1000'],

            /**
             * Task status. Only required if provided.
             *
             * @example in_progress
             */
            'status' => ['sometimes', 'string', Rule::in([
                Task::STATUS_PENDING,
                Task::STATUS_IN_PROGRESS,
                Task::STATUS_COMPLETED,
            ])],
        ];
    }
}
