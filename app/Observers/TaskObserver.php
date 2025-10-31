<?php

declare(strict_types=1);

namespace App\Observers;

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
            $newStatus = $task->status;
            $oldStatus = $task->getOriginal('status');

            // Set completed_at when status changes to completed
            if ($newStatus === Task::STATUS_COMPLETED && $oldStatus !== Task::STATUS_COMPLETED) {
                $task->completed_at = now();
            }

            // Clear completed_at if status changes away from completed
            if ($newStatus !== Task::STATUS_COMPLETED && $oldStatus === Task::STATUS_COMPLETED) {
                $task->completed_at = null;
            }
        }
    }
}
