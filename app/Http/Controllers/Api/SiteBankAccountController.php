<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SiteBankAccountRequest\ReplaceSiteBankAccountRequest;
use App\Http\Requests\Api\SiteBankAccountRequest\StoreSiteBankAccountRequest;
use App\Http\Requests\Api\SiteBankAccountRequest\UpdateSiteBankAccountRequest;
use App\Http\Resources\Api\SiteBankAccountResource;
use App\Models\SiteBankAccount;
use App\Models\Site;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class SiteBankAccountController extends ApiController
{
    use ApiResponses;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return SiteBankAccountResource::collection(SiteBankAccount::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSiteBankAccountRequest $request)
    {
        try {
            $attr = $request->mappedAttributes();

            if ($request->hasFile('accountQR')) {

                $file = $request->file('accountQR');
                $filename = uniqid() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('account_qr', $filename);

                $attr['account_qr'] = $filename;
            }

            return new SiteBankAccountResource(
                SiteBankAccount::create($attr)
            );

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to create a Site.', 401);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        try {
            $site_bank_account = SiteBankAccount::where('uuid', $uuid)->firstOrFail();
            return new SiteBankAccountResource($site_bank_account);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Site does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to view a Site.', 401);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSiteBankAccountRequest $request, string $uuid)
    {
        try {
            // fetch the site
            $site_bank_account = SiteBankAccount::where('uuid', $uuid)->firstOrFail();

            // update using the validated/mapped attributes
            $attr = $request->mappedAttributes();

            if ($request->hasFile('accountQR')) {

                $file = $request->file('accountQR');
                $filename = uniqid() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('account_qr', $filename);

                $attr['account_qr'] = $filename;
            }

            // update site
            $site_bank_account->update($attr);

            // return the updated site resource
            return new SiteBankAccountResource($site_bank_account);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Site does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to update a Site.', 401);
        }
    }
    /**
     * Replace the specified resource in storage.
     */
    public function replace(ReplaceSiteBankAccountRequest $request, string $uuid)
    {
        try {
            // replace policy
            // $this->isAble('replace', Site::class);

            $site_bank_account = SiteBankAccount::where('uuid', $uuid)->firstOrFail();
            $affected = $site_bank_account->update($request->mappedAttributes());

            return new SiteBankAccountResource($site_bank_account);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Site does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to replace a Site.', 401);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        try {
            $site_bank_account = SiteBankAccount::where('uuid', $uuid)->firstOrFail();
            $affected = $site_bank_account->delete();

            return $this->ok("Deleted $affected record.", []);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Site does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to delete a Site.', 401);
        }
    }

    /**
     * Display the specified resource via link.
     */
    public function getBankAccount(string $siteUuid)
    {
        $site = Site::where('uuid', $siteUuid)->firstOrFail();
        $bank = SiteBankAccount::where('site_id', $site->id)->first();

        return response()->json([
            'data' => [
                'site_uuid' => $site->uuid,
                'bank_uuid' => $bank?->uuid,
                'attributes' => $bank ? [
                    'bank_name' => $bank->bank_name,
                    'account_name' => $bank->account_name,
                    'account_number' => $bank->account_number,
                    'account_qr' => $bank->account_qr,
                    'account_qr_url' => $bank->account_qr
                        ? asset('storage/account_qr/' . $bank->account_qr)
                        : null,
                ] : null
            ]
        ]);
    }
}
