<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Composite index for common query patterns:
            // - Filtering tasks by user and status
            // - Ordering by creation date within user's tasks
            // This index supports queries like: WHERE user_id = ? AND status = ? ORDER BY created_at DESC
            $table->index(['user_id', 'status', 'created_at'], 'idx_tasks_user_status_created');

            // Index for filtering completed tasks with their completion date
            // Useful for analytics and reporting completed tasks
            $table->index(['user_id', 'completed_at'], 'idx_tasks_user_completed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('idx_tasks_user_status_created');
            $table->dropIndex('idx_tasks_user_completed');
        });
    }
};
