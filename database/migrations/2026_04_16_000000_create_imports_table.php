<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('entity_type', 64);
            $table->string('import_file', 512);
            $table->string('error_file', 512)->nullable();
            $table->string('status', 32);
            $table->unsignedInteger('total_items')->nullable();
            $table->unsignedInteger('total_errors')->nullable();
            $table->boolean('email_sent')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imports');
    }
};
