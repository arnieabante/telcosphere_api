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
        Schema::table('sites', function (Blueprint $table) {

            $table->string('account_number_pattern')
                ->nullable()
                ->after('receipt_id_yy_last_count');

            $table->boolean('enable_account_number_pattern')
                ->default(false)
                ->after('account_number_pattern');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {

            $table->dropColumn([
                'account_number_pattern',
                'enable_account_number_pattern'
            ]);

        });
    }
};