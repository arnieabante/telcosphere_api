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
        Schema::create('mikrotiks', function (Blueprint $table) {
            $table->id();
            // Tenant (SaaS)
            $table->integer('site_id')->nullable();
            $table->string('name'); // e.g. "Main Router"
            $table->string('ip_address'); // public or local IP
            $table->unsignedInteger('port')->default(8728); // 8728 or 8729
            $table->boolean('use_ssl')->default(false);
            $table->string('username');
            $table->text('password'); // encrypted
            $table->unsignedInteger('timeout')->default(3);
            $table->boolean('is_active')->default(true); // Status
            $table->timestamp('last_seen_at')->nullable(); // Monitoring
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mikrotiks');
    }
};
