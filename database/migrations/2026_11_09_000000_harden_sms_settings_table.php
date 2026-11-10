<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One settings row per site, with the API key encrypted at rest.
     */
    public function up(): void
    {
        // the unique index needs one row per site, the most recently created one is the live one
        $latestIds = DB::table('sms_settings')
            ->selectRaw('MAX(id) as id')
            ->groupBy('site_id')
            ->pluck('id');

        DB::table('sms_settings')->whereNotIn('id', $latestIds)->delete();

        Schema::table('sms_settings', function (Blueprint $table) {
            $table->unique('site_id');
        });

        DB::table('sms_settings')->whereNotNull('api_key')->orderBy('id')->each(function ($row) {
            if (!$this->isEncrypted($row->api_key)) {
                DB::table('sms_settings')
                    ->where('id', $row->id)
                    ->update(['api_key' => Crypt::encryptString($row->api_key)]);
            }
        });
    }

    public function down(): void
    {
        DB::table('sms_settings')->whereNotNull('api_key')->orderBy('id')->each(function ($row) {
            if ($this->isEncrypted($row->api_key)) {
                DB::table('sms_settings')
                    ->where('id', $row->id)
                    ->update(['api_key' => Crypt::decryptString($row->api_key)]);
            }
        });

        Schema::table('sms_settings', function (Blueprint $table) {
            $table->dropUnique(['site_id']);
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
