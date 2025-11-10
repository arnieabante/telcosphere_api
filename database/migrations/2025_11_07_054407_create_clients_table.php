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
            $table->string('first_name')->unique();
            $table->string('middle_name');
            $table->string('last_name');
            $table->string('mobile_no');
            $table->string('email');
            $table->string('house_no');
            $table->string('account_no');
            $table->string('installation_date');
            $table->integer('status');
            $table->string('inactive_date')->nullable();
            $table->string('notes');
            $table->string('facebook_profile_url');
            $table->integer('billing_category_id');
            $table->integer('server_id');
            $table->integer('internet_plan_id');
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
