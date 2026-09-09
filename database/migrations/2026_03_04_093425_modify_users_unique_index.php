<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']); // remove old unique
            $table->dropUnique(['email']); // remove old unique
            $table->unique(['site_id', 'username']); // add composite unique
            $table->unique(['site_id', 'email']); // add composite unique
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['site_id', 'username']);
            $table->dropUnique(['site_id', 'email']);
            $table->unique('username');
            $table->unique('email');
        });
    }
};
