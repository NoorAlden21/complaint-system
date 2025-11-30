<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_versions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('complaint_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedInteger('version_number');

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
            ])->nullable();

            $table->enum('priority', [
                'low',
                'medium',
                'high',
                'urgent',
            ])->default('medium')->nullable();

            $table->foreignId('category_id')
                ->nullable()
                ->constrained('complaint_categories')
                ->nullOnDelete();

            $table->foreignId('department_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('region_id')
                ->nullable()
                ->constrained('regions')
                ->nullOnDelete();

            $table->foreignId('changed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // وصف الحدث: إنشاء الشكوى، تغيير حالة، طلب معلومات إضافية...
            $table->text('note')->nullable();

            $table->timestamps();

            $table->unique(['complaint_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_versions');
    }
};
