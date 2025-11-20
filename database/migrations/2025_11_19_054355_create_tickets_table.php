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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->integer('site_id');
            $table->integer('client_id');
            $table->string('name');
            $table->string('description')->nullable();
            $table->integer('category_id');
            $table->date('requested_date');
            $table->date('due_date')->nullable();
            $table->integer('assigned_to')->nullable();
            $table->string('remarks')->nullable();
            $table->string('status')->nullable();
            $table->boolean('is_active');
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
        Schema::dropIfExists('tickets');
    }
};
