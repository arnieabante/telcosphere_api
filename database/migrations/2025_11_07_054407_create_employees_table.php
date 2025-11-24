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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->integer('site_id');
            $table->string('firstname')->unique();
            $table->string('middlename')->nullable();
            $table->string('lastname');
            $table->date('birth_date')->nullable();
            $table->string('gender')->nullable();
            $table->string('civil_status')->nullable();
            $table->string('email_address')->nullable();
            $table->string('contact_no')->nullable();
            $table->string('home_address')->nullable();
            $table->string('emergency_contact_no')->nullable();
            $table->string('employee_id')->nullable();
            $table->date('date_hired');
            $table->string('department')->nullable();
            $table->string('designation')->nullable();
            $table->string('work_location')->nullable();
            $table->string('access_level');
            $table->integer('user_id')->nullable();
            $table->string('shift_schedule_from')->nullable();
            $table->string('shift_schedule_to')->nullable();
            $table->float('salary_rate_per_day');
            $table->float('hourly_rate_per_day');
            $table->string('payment_method');
            $table->string('bank_name')->nullable();
            $table->string('bank_account_no')->nullable();
            $table->string('sss_no')->nullable();
            $table->string('pagibig_no')->nullable();
            $table->string('philhealth_no')->nullable();
            $table->string('tin')->nullable();
            $table->string('employee_type');
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
        Schema::dropIfExists('employees');
    }
};
