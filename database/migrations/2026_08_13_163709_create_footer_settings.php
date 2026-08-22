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
        Schema::create('footer_settings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->integer('site_id');
            $table->string('company_footer_tagline')->nullable();
            $table->string('company_email')->nullable();
            $table->string('company_telephone')->nullable();
            $table->string('company_cellphone')->nullable();
            $table->string('company_address')->nullable();
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
        Schema::dropIfExists('footer_settings');
    }
};
