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
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->string('company_logo')->nullable();
            $table->string('company_banner')->nullable();
            $table->string('site_url')->unique();
            $table->string('company_name')->unique();
            $table->string('company_address');
            $table->string('invoice_id_pattern')->nullable();
            $table->integer('invoice_id_yy_last_count')->default(0);
            $table->string('receipt_id_pattern')->nullable();
            $table->integer('receipt_id_yy_last_count')->default(0);
            $table->string('payment_details')->nullable();
            $table->string('company_email')->nullable();
            $table->string('company_phone')->nullable();
            $table->string('company_telephone')->nullable();
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
        Schema::dropIfExists('sites');
    }
};
