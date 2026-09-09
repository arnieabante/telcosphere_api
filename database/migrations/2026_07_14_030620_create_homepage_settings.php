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
        Schema::create('homepage_settings', function (Blueprint $table) {
            $table->id();

            $table->integer('site_id');
            // Hero Section
            $table->boolean('hero_enabled')->default(true);
            $table->string('hero_title')->nullable();
            $table->string('hero_subtitle')->nullable();
            $table->string('primary_button_text')->nullable();
            $table->string('primary_button_url')->nullable();

            $table->string('background_image')->nullable();
            $table->string('hero_image')->nullable();
            $table->enum('text_alignment', [
                'left',
                'center',
                'right'
            ])->default('center');
            $table->unsignedTinyInteger('overlay_opacity')->default(40);
            $table->timestamps();
            $table->unique('site_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('homepage_settings');
    }
};