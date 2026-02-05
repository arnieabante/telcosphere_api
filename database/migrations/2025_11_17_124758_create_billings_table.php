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
        Schema::create('billings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->integer('site_id');
            $table->integer('client_id');
            $table->string('invoice_number');
            $table->integer('billing_type');
            $table->timestamp('billing_date');
            $table->tinyText('billing_description')->nullable();
            $table->tinyText('billing_remarks')->nullable();
            $table->decimal('billing_total', 8, 2);
            $table->decimal('billing_offset', 8, 2)->default(0);
            $table->decimal('billing_balance', 8, 2)->default(0);
            $table->string('billing_status');
            $table->date('billing_cutoff')->nullable();
            $table->date('disconnection_date')->nullable();
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
        Schema::dropIfExists('billings');
    }
};
