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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->integer('site_id');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('mobile_no');
            $table->string('email')->nullable()->nullable();
            $table->string('house_no');
            $table->float('latitude')->nullable();
            $table->float('longitude')->nullable();
            $table->string('account_no')->nullable();
            $table->string('installation_date');
            $table->decimal('installation_fee', 8, 2)->default(0.00);
            $table->decimal('balance_from_prev_billing', 8, 2)->default(0.00);
            $table->decimal('prorate_fee', 8, 2)->default(0.00);
            $table->date('prorate_start_date')->nullable();
            $table->date('prorate_end_date')->nullable();
            $table->string('prorate_fee_remarks')->nullable();
            $table->integer('prorate_fee_status')->nullable(); //0 - Pending, 1-Billed, 2-Paid
            $table->string('inactive_date')->nullable();
            $table->string('notes')->nullable();
            $table->string('facebook_profile_url')->nullable();
            $table->integer('billing_category_id');
            $table->integer('server_id');
            $table->integer('internet_plan_id');
            $table->string('last_auto_billing_date')->nullable();
            $table->integer('is_active');
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
        Schema::dropIfExists('clients');
    }
};
