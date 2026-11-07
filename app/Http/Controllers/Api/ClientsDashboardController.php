<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ClientsDashboardRequest\StoreClientTicketRequest;
use App\Http\Resources\Api\ClientBillingResource;
use App\Http\Resources\Api\ClientTicketResource;
use App\Http\Resources\Api\SiteBankAccountResource;
use App\Models\Billing;
use App\Models\Client;
use App\Models\SiteBankAccount;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Services\ClientOverviewService;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

/**
 * Client portal. The client always comes from the logged in user and never from
 * the request, so a client can only ever reach their own records.
 */
class ClientsDashboardController extends Controller
{
    use ApiResponses;

    public function overview(Request $request, ClientOverviewService $service)
    {
        return $this->ok('Client overview.', $service->getSummary($this->client($request)));
    }

    public function billings(Request $request)
    {
        $billings = Billing::where('client_id', $this->client($request)->id)
            ->where('is_active', 1)
            ->orderBy('billing_date', 'desc')
            ->paginate($request->integer('per_page', 10));

        return ClientBillingResource::collection($billings);
    }

    public function billingPayment(Request $request, string $uuid)
    {
        $client = $this->client($request);

        $billing = Billing::where('client_id', $client->id)
            ->where('is_active', 1)
            ->where('uuid', $uuid)
            ->first();

        if (!$billing) {
            return $this->error('Billing does not exist.', 404);
        }

        $bankAccount = SiteBankAccount::where('site_id', $client->site_id)->first();

        return $this->ok('Billing payment details.', [
            'billing' => new ClientBillingResource($billing),
            'bankAccount' => $bankAccount ? new SiteBankAccountResource($bankAccount) : null,
        ]);
    }

    public function tickets(Request $request)
    {
        $tickets = Ticket::with(['client', 'ticketCategory', 'assignedTo'])
            ->where('client_id', $this->client($request)->id)
            ->where('is_active', 1)
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 10));

        return ClientTicketResource::collection($tickets);
    }

    public function storeTicket(StoreClientTicketRequest $request)
    {
        $client = $this->client($request);

        $ticket = Ticket::create([
            'site_id' => $client->site_id,
            'ticket_type' => 'Client',
            'client_id' => $client->id,
            'requestor_name' => trim("{$client->first_name} {$client->last_name}"),
            'requestor_location' => $client->house_no,
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'category_id' => $request->input('categoryId'),
            'remarks' => $request->input('remarks'),
            'requested_date' => today(),
            'status' => 'new',
        ]);

        return new ClientTicketResource($ticket->load(['client', 'ticketCategory', 'assignedTo']));
    }

    public function ticketCategories(Request $request)
    {
        $this->client($request);

        return $this->ok('Ticket categories.', TicketCategory::where('is_active', 1)
            ->orderBy('name')
            ->get(['id', 'name']));
    }

    private function client(Request $request): Client
    {
        $client = $request->user()->client;

        abort_unless($client, 403, 'This account is not linked to a client.');

        return $client;
    }
}
