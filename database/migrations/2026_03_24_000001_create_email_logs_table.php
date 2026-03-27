<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->text('from_address')->nullable();
            $table->text('to_addresses')->nullable();
            $table->text('cc_addresses')->nullable();
            $table->text('bcc_addresses')->nullable();
            $table->text('subject')->nullable();
            $table->longText('body')->nullable();
            $table->timestamp('sent_at');

            $table->index('sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
