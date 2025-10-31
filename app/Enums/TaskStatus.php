<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Task status enumeration.
 *
 * Represents the lifecycle states of a task:
 * - Pending: Task is created but not yet started
 * - InProgress: Task is actively being worked on
 * - Completed: Task has been finished (triggers completed_at timestamp)
 */
enum TaskStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    /**
     * Get all available status values.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get a human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
        };
    }

    /**
     * Check if the task is completed.
     */
    public function isCompleted(): bool
    {
        return $this === self::Completed;
    }

    /**
     * Check if the task is in progress.
     */
    public function isInProgress(): bool
    {
        return $this === self::InProgress;
    }

    /**
     * Check if the task is pending.
     */
    public function isPending(): bool
    {
        return $this === self::Pending;
    }
}
