<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A site can have bank details without having a QR code to show.
     */
    public function up(): void
    {
        Schema::table('site_bank_accounts', function (Blueprint $table) {
            $table->string('account_qr')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('site_bank_accounts', function (Blueprint $table) {
            $table->string('account_qr')->nullable(false)->change();
        });
    }
};
