<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropUnique(['name']); // remove old unique
            $table->unique(['site_id', 'name']); // add composite unique
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropUnique(['site_id', 'name']);
            $table->unique('name');
        });
    }
};
