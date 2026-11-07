<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SaveSiteBankAccountRequest;
use App\Http\Resources\Api\SiteBankAccountResource;
use App\Models\SiteBankAccount;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * The bank details clients pay to. Each site has one set, always the logged in user's site.
 */
class SiteBankAccountController extends Controller
{
    use ApiResponses;

    public function show(Request $request)
    {
        $account = SiteBankAccount::where('site_id', $request->user()->site_id)->first();

        return $account
            ? new SiteBankAccountResource($account)
            : $this->ok('No bank details saved yet.', null);
    }

    public function save(SaveSiteBankAccountRequest $request)
    {
        $account = SiteBankAccount::firstOrNew(['site_id' => $request->user()->site_id]);

        $account->fill([
            'bank_name' => $request->input('bankName'),
            'account_name' => $request->input('accountName'),
            'account_number' => $request->input('accountNumber'),
        ]);

        if ($request->hasFile('accountQR')) {
            if ($account->account_qr) {
                Storage::disk('public')->delete($account->account_qr);
            }

            $account->account_qr = $request->file('accountQR')->store('account_qr', 'public');
        }

        $account->save();

        return new SiteBankAccountResource($account);
    }
}
