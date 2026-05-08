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
        Schema::table('internetplans', function (Blueprint $table) {
            $table->dropUnique(['site_id', 'name']); // remove old unique
            $table->unique(['site_id', 'name', 'updated_at']); // add composite unique
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('internetplans', function (Blueprint $table) {
            $table->dropUnique(['site_id', 'name', 'updated_at']);
            $table->unique(['site_id', 'name']);
        });
    }
};
