<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Endpoints for managing tasks. All endpoints require authentication and automatically
 * scope operations to the authenticated user's tasks only.
 *
 * Tasks have three statuses:
 * - pending: Task is created but not started
 * - in_progress: Task is currently being worked on
 * - completed: Task is finished (sets completed_at timestamp)
 */
#[Group('Task Management', weight: 2)]
class TaskController extends Controller
{
    /**
     * List all tasks for the authenticated user
     *
     * Retrieves all tasks belonging to the authenticated user. Tasks are returned
     * with their full details including status and timestamps. This endpoint only
     * returns tasks owned by the current user - users cannot see other users' tasks.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $tasks = $request->user()->tasks()->latest()->get();

        /**
         * Collection of tasks for the authenticated user.
         *
         * @body TaskResource[]
         */
        return TaskResource::collection($tasks);
    }

    /**
     * Create a new task
     *
     * Creates a new task for the authenticated user. The task will be automatically
     * assigned to the current user. Status defaults to 'pending' if not provided.
     */
    public function store(StoreTaskRequest $request): TaskResource
    {
        $task = $request->user()->tasks()->create([
            'title' => $request->title,
            'description' => $request->description,
            'status' => $request->status ?? TaskStatus::Pending,
        ]);

        /**
         * Newly created task.
         *
         * @status 201
         *
         * @body TaskResource
         */
        return new TaskResource($task);
    }

    /**
     * View a specific task
     *
     * Retrieves detailed information about a specific task. Users can only view
     * their own tasks. Attempting to view another user's task will return a 404.
     */
    public function show(Request $request, string $id): TaskResource
    {
        $task = $request->user()->tasks()->findOrFail($id);

        /**
         * Task details.
         *
         * @body TaskResource
         */
        return new TaskResource($task);
    }

    /**
     * Update a task
     *
     * Updates an existing task. Users can only update their own tasks. All fields
     * are optional - only provided fields will be updated. When status is changed
     * to 'completed', the completed_at timestamp is automatically set by the TaskObserver.
     */
    public function update(UpdateTaskRequest $request, string $id): TaskResource
    {
        $task = $request->user()->tasks()->findOrFail($id);

        // Observer handles completed_at timestamp automatically
        $task->update($request->only(['title', 'description', 'status']));

        /**
         * Updated task details.
         *
         * @body TaskResource
         */
        return new TaskResource($task);
    }

    /**
     * Delete a task
     *
     * Permanently deletes a task. Users can only delete their own tasks.
     * This action cannot be undone.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $task = $request->user()->tasks()->findOrFail($id);
        $task->delete();

        /**
         * No content response on successful deletion.
         *
         * @status 204
         */
        return response()->json(null, 204);
    }
}
