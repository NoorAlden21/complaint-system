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
        Schema::create('report_exports', function (Blueprint $table) {
            $table->id();

            $table->string('type', 50);      // performance
            $table->string('format', 10);    // pdf|csv

            $table->json('filters');         // from/to/department_id/...

            $table->foreignId('requested_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->enum('status', ['queued', 'running', 'success', 'failed'])
                ->default('queued');

            $table->timestamp('queued_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->string('file_disk')->nullable(); // reports
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();

            $table->text('error_message')->nullable();

            // مفيد للتدقيق لاحقًا (بدون نظام Audit كامل الآن)
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('requested_by');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_exports');
    }
};
