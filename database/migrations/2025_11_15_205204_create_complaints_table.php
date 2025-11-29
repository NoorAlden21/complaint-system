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

            $table->string('title');
            $table->text('description');

            $table->enum('status', [
                'pending',
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
            ])->default('medium');

            $table->foreignId('category_id')
                ->constrained('complaint_categories');

            $table->foreignId('department_id')
                ->nullable()
                ->constrained('departments');

            $table->foreignId('region_id')
                ->constrained('regions');

            $table->foreignId('created_by')
                ->constrained('users');

            // $table->foreignId('assigned_to')
            //     ->nullable()
            //     ->constrained('users');

            //$table->boolean('is_anonymous')->default(false);

            $table->timestamp('sla_due_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('resolution_summary')->nullable();

            $table->timestamps();

            // Indexes لتحسين الأداء
            $table->index('status');
            $table->index('priority');
            $table->index('department_id');
            $table->index('created_by');
            //$table->index('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
