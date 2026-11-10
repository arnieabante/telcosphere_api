<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->integer('site_id')->index();
            $table->unsignedBigInteger('client_id')->nullable()->index();
            $table->unsignedBigInteger('billing_id')->nullable();
            $table->string('type', 40);
            $table->string('recipient', 20)->nullable();
            $table->text('message')->nullable();
            $table->string('status', 20);
            $table->string('provider_message_id')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            // a billing gets each kind of SMS once; test sends have no billing and are not constrained
            $table->unique(['billing_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
