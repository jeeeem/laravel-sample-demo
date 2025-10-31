<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;

/**
 * @group Task Management
 *
 * Endpoints for managing tasks. All endpoints require authentication and automatically
 * scope operations to the authenticated user's tasks only.
 *
 * Tasks have three statuses:
 * - pending: Task is created but not started
 * - in_progress: Task is currently being worked on
 * - completed: Task is finished (sets completed_at timestamp)
 */
class TaskController extends Controller
{
    /**
     * List all tasks for the authenticated user
     *
     * Retrieves all tasks belonging to the authenticated user. Tasks are returned
     * with their full details including status and timestamps. This endpoint only
     * returns tasks owned by the current user - users cannot see other users' tasks.
     *
     * @authenticated
     *
     * @response 200 [
     *   {
     *     "id": 1,
     *     "title": "Complete project documentation",
     *     "description": "Write comprehensive API docs",
     *     "status": "in_progress",
     *     "completed_at": null,
     *     "created_at": "2025-10-31T10:00:00Z",
     *     "updated_at": "2025-10-31T12:00:00Z"
     *   }
     * ]
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $tasks = $request->user()->tasks()->latest()->get();

        return TaskResource::collection($tasks);
    }

    /**
     * Create a new task
     *
     * Creates a new task for the authenticated user. The task will be automatically
     * assigned to the current user. Status defaults to 'pending' if not provided.
     *
     * @authenticated
     *
     * @response 201 {
     *   "id": 1,
     *   "title": "Complete project documentation",
     *   "description": "Write comprehensive API docs",
     *   "status": "pending",
     *   "completed_at": null,
     *   "created_at": "2025-10-31T10:00:00Z",
     *   "updated_at": "2025-10-31T10:00:00Z"
     * }
     *
     * @response 422 {
     *   "message": "The title field is required.",
     *   "errors": {
     *     "title": ["The title field is required."]
     *   }
     * }
     */
    public function store(StoreTaskRequest $request): TaskResource
    {
        $task = $request->user()->tasks()->create([
            'title' => $request->title,
            'description' => $request->description,
            'status' => $request->status ?? Task::STATUS_PENDING,
        ]);

        return new TaskResource($task);
    }

    /**
     * View a specific task
     *
     * Retrieves detailed information about a specific task. Users can only view
     * their own tasks. Attempting to view another user's task will return a 404.
     *
     * @authenticated
     *
     * @response 200 {
     *   "id": 1,
     *   "title": "Complete project documentation",
     *   "description": "Write comprehensive API docs",
     *   "status": "completed",
     *   "completed_at": "2025-10-31T15:00:00Z",
     *   "created_at": "2025-10-31T10:00:00Z",
     *   "updated_at": "2025-10-31T15:00:00Z"
     * }
     *
     * @response 404 {
     *   "message": "No query results for model [App\\Models\\Task]."
     * }
     */
    public function show(Request $request, string $id): TaskResource
    {
        $task = $request->user()->tasks()->findOrFail($id);

        return new TaskResource($task);
    }

    /**
     * Update a task
     *
     * Updates an existing task. Users can only update their own tasks. All fields
     * are optional - only provided fields will be updated. When status is changed
     * to 'completed', the completed_at timestamp is automatically set.
     *
     * @authenticated
     *
     * @response 200 {
     *   "id": 1,
     *   "title": "Complete project documentation",
     *   "description": "Updated description",
     *   "status": "completed",
     *   "completed_at": "2025-10-31T15:00:00Z",
     *   "created_at": "2025-10-31T10:00:00Z",
     *   "updated_at": "2025-10-31T15:00:00Z"
     * }
     *
     * @response 404 {
     *   "message": "No query results for model [App\\Models\\Task]."
     * }
     *
     * @response 422 {
     *   "message": "The status must be one of: pending, in_progress, completed.",
     *   "errors": {
     *     "status": ["The status must be one of: pending, in_progress, completed."]
     *   }
     * }
     */
    public function update(UpdateTaskRequest $request, string $id): TaskResource
    {
        $task = $request->user()->tasks()->findOrFail($id);

        $data = $request->only(['title', 'description', 'status']);

        // Set completed_at when status changes to completed
        if (isset($data['status']) && $data['status'] === Task::STATUS_COMPLETED && $task->status !== Task::STATUS_COMPLETED) {
            $data['completed_at'] = now();
        }

        // Clear completed_at if status is changed from completed to something else
        if (isset($data['status']) && $data['status'] !== Task::STATUS_COMPLETED && $task->status === Task::STATUS_COMPLETED) {
            $data['completed_at'] = null;
        }

        $task->update($data);

        return new TaskResource($task);
    }

    /**
     * Delete a task
     *
     * Permanently deletes a task. Users can only delete their own tasks.
     * This action cannot be undone.
     *
     * @authenticated
     *
     * @response 204
     *
     * @response 404 {
     *   "message": "No query results for model [App\\Models\\Task]."
     * }
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $task = $request->user()->tasks()->findOrFail($id);
        $task->delete();

        return response()->json(null, 204);
    }
}
