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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            
            // User Information (Denormalized)
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_name')->nullable();
            $table->string('user_email')->nullable();
            $table->string('user_role')->nullable();

            // Technical Information
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('os')->nullable();
            $table->string('browser')->nullable();
            
            // Request Information
            $table->string('http_method', 10)->nullable();
            $table->string('endpoint')->nullable();
            $table->string('module')->nullable()->index();
            $table->string('action')->index();

            // Resource Affected (Polymorphic)
            $table->nullableMorphs('auditable');

            // Execution Information
            $table->string('status')->index(); // success, failed, canceled
            $table->integer('status_code')->nullable();
            $table->integer('duration')->nullable(); // milliseconds
            $table->string('severity')->default('info')->index(); // info, warning, critical

            // Detail and differences
            $table->text('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('error_details')->nullable(); // For exceptions and stack traces

            // Grouping related actions
            $table->string('correlation_id')->nullable()->index();

            // Review status
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();

            $table->timestamps(); // includes created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
