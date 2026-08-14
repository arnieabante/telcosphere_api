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
        Schema::create('sms_settings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->integer('site_id');

            $table->string('provider')->default('semaphore');
            $table->text('api_key')->nullable();
            $table->string('sender_name')->nullable();
            $table->string('api_url')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->boolean('test_mode')->default(false);
            $table->unsignedInteger('connection_timeout')->default(30);
            $table->unsignedInteger('retry_attempts')->default(3);

            $table->integer('created_by');
            $table->integer('updated_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_settings');
    }
};
