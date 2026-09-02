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
        Schema::create('cta_settings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->integer('site_id');
            $table->string('cta_title')->nullable();
            $table->string('cta_text')->nullable();
            $table->string('cta_button')->nullable();
            $table->string('cta_label')->nullable();
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
        Schema::dropIfExists('cta_settings');
    }
};
