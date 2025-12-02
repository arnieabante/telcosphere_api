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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->integer('site_id');
            $table->integer('client_id');
            $table->string('receipt_no');
            $table->integer('payment_name');
            $table->integer('payment_amount');
            $table->string('payment_method');
            $table->string('reference')->nullable();
            $table->integer('discount')->nullable();
            $table->integer('discount_total')->nullable();
            $table->string('discount_reason');
            $table->date('payment_date');
            $table->integer('collected_by');
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
        Schema::dropIfExists('payments');
    }
};
