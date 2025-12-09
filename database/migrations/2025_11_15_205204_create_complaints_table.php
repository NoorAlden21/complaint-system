<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->nullable()->unique();

            $table->string('title');
            $table->text('description');

            $table->enum('status', [
                'pending',
                'needs_more_info',
                'open',
                'in_progress',
                'resolved',
                'closed',
                'rejected',
            ])->default('pending');

            $table->enum('priority', [
                'low',
                'medium',
                'high',
                'urgent',
            ])->default('medium')->nullable();

            $table->foreignId('category_id')
                ->constrained('complaint_categories');

            $table->foreignId('department_id')
                ->nullable()
                ->constrained('departments');

            $table->foreignId('region_id')
                ->constrained('regions');

            $table->foreignId('created_by')
                ->constrained('users');

            $table->timestamp('sla_due_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('resolution_summary')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('priority');
            $table->index('department_id');
            $table->index('region_id');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
