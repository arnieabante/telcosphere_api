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
        Schema::create('payment_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->integer('site_id')->nullable();
            $table->integer('billing_id');
            $table->string('particulars');
            $table->decimal('amount', 12, 2);
            $table->decimal('amount_paid', 12, 2);
            $table->decimal('amount_balance', 12, 2);
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
        Schema::dropIfExists('payment_items');
    }
};
