<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\TaskStatus;
use App\Models\Task;

class TaskObserver
{
    /**
     * Handle the Task "updating" event.
     *
     * Automatically manages the completed_at timestamp based on status changes:
     * - Sets completed_at when status changes to 'completed'
     * - Clears completed_at when status changes away from 'completed'
     */
    public function updating(Task $task): void
    {
        // Check if status is being changed
        if ($task->isDirty('status')) {
            // Get new status (cast to enum by model)
            $newStatus = $task->status;

            // Get old status (raw DB value from getOriginal - could be string or enum)
            $oldStatusRaw = $task->getOriginal('status');

            // Convert to enum if needed
            if (is_string($oldStatusRaw)) {
                $oldStatus = TaskStatus::from($oldStatusRaw);
            } else {
                /** @var TaskStatus $oldStatusRaw */
                $oldStatus = $oldStatusRaw;
            }

            // Set completed_at when status changes to completed
            if ($newStatus === TaskStatus::Completed && $oldStatus !== TaskStatus::Completed) {
                $task->completed_at = now();
            }

            // Clear completed_at if status changes away from completed
            if ($newStatus !== TaskStatus::Completed && $oldStatus === TaskStatus::Completed) {
                $task->completed_at = null;
            }
        }
    }
}
