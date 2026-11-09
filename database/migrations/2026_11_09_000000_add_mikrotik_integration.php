<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL cannot roll back schema changes, so stop before the first one
        if (blank(config('app.key'))) {
            throw new RuntimeException('APP_KEY is empty. Run "php artisan key:generate" before this migration: it encrypts stored passwords.');
        }

        // every step checks first, so a run that failed part way can be resumed
        $this->addMissingColumns('mikrotiks', [
            'server_id' => fn (Blueprint $table) => $table->unsignedBigInteger('server_id')->nullable()->after('site_id')->index(),
            'last_checked_at' => fn (Blueprint $table) => $table->timestamp('last_checked_at')->nullable()->after('last_seen_at'),
            'last_error' => fn (Blueprint $table) => $table->text('last_error')->nullable()->after('last_checked_at'),
        ]);

        $this->addMissingColumns('clients', [
            'mikrotik_id' => fn (Blueprint $table) => $table->unsignedBigInteger('mikrotik_id')->nullable()->after('server_id')->index(),
            'network_status' => fn (Blueprint $table) => $table->string('network_status', 30)->default('unmanaged')->after('pppoe_password'),
            'network_synced_at' => fn (Blueprint $table) => $table->timestamp('network_synced_at')->nullable()->after('network_status'),
            'network_error' => fn (Blueprint $table) => $table->text('network_error')->nullable()->after('network_synced_at'),
            'suspension_exempt_until' => fn (Blueprint $table) => $table->date('suspension_exempt_until')->nullable()->after('network_error'),
        ]);

        // encrypted values do not fit in a varchar(255)
        Schema::table('clients', function (Blueprint $table) {
            $table->text('pppoe_password')->nullable()->change();
        });

        $this->addMissingColumns('internetplans', [
            'mikrotik_profile' => fn (Blueprint $table) => $table->string('mikrotik_profile')->nullable()->after('name'),
        ]);

        if (! Schema::hasTable('mikrotik_settings')) {
            Schema::create('mikrotik_settings', function (Blueprint $table) {
                $table->id();
                $table->integer('site_id')->unique();
                $table->boolean('is_enabled')->default(false);
                $table->boolean('dry_run')->default(true);
                $table->string('disconnect_window_start', 5)->default('08:00');
                $table->string('disconnect_window_end', 5)->default('17:00');
                $table->unsignedTinyInteger('max_suspend_percent')->default(30);
                $table->string('timezone', 64)->default('Asia/Manila');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('mikrotik_logs')) {
            Schema::create('mikrotik_logs', function (Blueprint $table) {
                $table->id();
                $table->integer('site_id')->index();
                $table->unsignedBigInteger('mikrotik_id')->nullable()->index();
                // no foreign key: the audit trail must outlive a deleted client
                $table->unsignedBigInteger('client_id')->nullable()->index();
                $table->string('pppoe_username')->nullable();
                $table->string('action', 30);
                $table->string('result', 20);
                $table->string('trigger', 30)->nullable();
                $table->text('message')->nullable();
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->timestamps();
                $table->index(['site_id', 'created_at']);
            });
        }

        $this->encryptColumn('mikrotiks', 'password');
        $this->encryptColumn('clients', 'pppoe_password');
    }

    public function down(): void
    {
        $this->decryptColumn('mikrotiks', 'password');
        $this->decryptColumn('clients', 'pppoe_password');

        Schema::dropIfExists('mikrotik_logs');
        Schema::dropIfExists('mikrotik_settings');

        Schema::table('internetplans', function (Blueprint $table) {
            $table->dropColumn('mikrotik_profile');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['mikrotik_id', 'network_status', 'network_synced_at', 'network_error', 'suspension_exempt_until']);
        });

        Schema::table('mikrotiks', function (Blueprint $table) {
            $table->dropColumn(['server_id', 'last_checked_at', 'last_error']);
        });
    }

    /**
     * @param  array<string, callable(Blueprint): mixed>  $columns  column name => definition, in order
     */
    private function addMissingColumns(string $table, array $columns): void
    {
        foreach ($columns as $column => $definition) {
            if (! Schema::hasColumn($table, $column)) {
                Schema::table($table, $definition);
            }
        }
    }

    private function encryptColumn(string $table, string $column): void
    {
        DB::table($table)->whereNotNull($column)->orderBy('id')->chunkById(200, function ($rows) use ($table, $column) {
            foreach ($rows as $row) {
                if (! $this->isEncrypted($row->{$column})) {
                    DB::table($table)->where('id', $row->id)->update([$column => Crypt::encryptString($row->{$column})]);
                }
            }
        });
    }

    private function decryptColumn(string $table, string $column): void
    {
        DB::table($table)->whereNotNull($column)->orderBy('id')->chunkById(200, function ($rows) use ($table, $column) {
            foreach ($rows as $row) {
                if ($this->isEncrypted($row->{$column})) {
                    DB::table($table)->where('id', $row->id)->update([$column => Crypt::decryptString($row->{$column})]);
                }
            }
        });
    }

    private function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }
};
