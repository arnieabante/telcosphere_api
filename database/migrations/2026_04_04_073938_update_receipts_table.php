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
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropColumn([
                'receipt_no', 'receipt_last_YY_no', 'receipt_YY'
            ]);
            $table->integer('site_id')->after('id');
            $table->string('receipt_number')->nullable()->after('site_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->string('receipt_no')->nullable();
            $table->integer('receipt_last_YY_no')->nullable();
            $table->string('receipt_YY')->nullable();
            $table->dropColumn([
                'site_id', 'receipt_number'
            ]);
        });
    }
};
