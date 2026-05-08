<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
    {
        Schema::create('peso_wifi_harvests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->integer('site_id')->nullable();
            $table->integer('peso_wifi_client_id');
            $table->decimal('amount_harvested', 12, 2)->default(0);
            $table->decimal('less_internet', 12, 2)->default(0);
            $table->decimal('less_electricity', 12, 2)->default(0);
            $table->decimal('less_others', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('resellers_income', 12, 2)->default(0);
            $table->decimal('owner_income', 12, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->integer('collected_by')->nullable();
            $table->boolean('is_active');
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peso_wifi_harvests');
    }
};
