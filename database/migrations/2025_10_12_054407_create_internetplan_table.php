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
        Schema::create('internetplans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->integer('site_id');
            $table->string('name')->unique();
            $table->text('monthly_subscription');
            $table->boolean('is_active');
            $table->longText('icon')->nullable();
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
        Schema::dropIfExists('internetplans');
    }
};
