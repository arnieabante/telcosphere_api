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
            $table->integer('site_id')->nullable();
            $table->integer('client_id');
            $table->string('receipt_no');
            $table->date('collection_date')->nullable();
            $table->string('collected_by')->nullable();
            $table->string('payment_method', 50);
            $table->string('reference')->nullable();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount', 12, 2)->nullable();
            $table->decimal('total', 12, 2);
            $table->decimal('amount_received', 12, 2);
            $table->decimal('amount_change', 12, 2);
            $table->decimal('amount_paid', 12, 2);
            $table->string('discount_reason')->nullable();
            $table->decimal('balance', 12, 2);
            $table->boolean('is_active');
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
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
