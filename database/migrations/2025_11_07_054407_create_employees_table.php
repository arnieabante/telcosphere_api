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
            $table->string('middlename');
            $table->string('lastname');
            $table->date('birth_date');
            $table->string('gender');
            $table->string('civil_status');
            $table->string('email_address');
            $table->string('contact_no');
            $table->string('home_address');
            $table->string('emergency_contact_no');
            $table->string('employee_id');
            $table->date('date_hired');
            $table->string('department');
            $table->string('designation');
            $table->string('work_location');
            $table->string('access_level');
            $table->integer('user_id')->nullable();
            $table->string('shift_schedule_from');
            $table->string('shift_schedule_to');
            $table->float('salary_rate_per_day');
            $table->float('hourly_rate_per_day');
            $table->string('payment_method');
            $table->string('bank_name');
            $table->string('bank_account_no');
            $table->string('sss_no');
            $table->string('pagibig_no');
            $table->string('philhealth_no');
            $table->string('tin');
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
