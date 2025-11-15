<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_status_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('complaint_id')
                ->constrained('complaints')
                ->cascadeOnDelete();

            $table->enum('from_status', [
                'pending',
                'open',
                'in_progress',
                'resolved',
                'closed',
                'rejected',
            ])->nullable();

            $table->enum('to_status', [
                'pending',
                'open',
                'in_progress',
                'resolved',
                'closed',
                'rejected',
            ]);

            $table->foreignId('changed_by')
                ->constrained('users');

            $table->text('note')->nullable();

            $table->timestamp('changed_at')->useCurrent();
            $table->timestamps();

            $table->index('complaint_id');
            $table->index('to_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_status_histories');
    }
};
