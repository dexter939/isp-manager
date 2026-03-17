<?php

declare(strict_types=1);

namespace Modules\Network\Http\Controllers;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Network\Http\Requests\ParentalControlSubscriptionRequest;
use Modules\Network\Http\Requests\UpdateParentalControlRequest;
use Modules\Network\Models\ParentalControlProfile;
use Modules\Network\Models\ParentalControlSubscription;
use Modules\Network\Services\DnsFilter\DnsFilterResolverInterface;
use Modules\Network\Services\ParentalControlService;

class ParentalControlController extends ApiController
{
    public function __construct(
        private readonly ParentalControlService $service,
        private readonly DnsFilterResolverInterface $resolver,
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * Elenco dei profili disponibili per il tenant corrente.
     */
    public function profiles(Request $request): JsonResponse
    {
        $profiles = ParentalControlProfile::where('tenant_id', $request->user()->tenant_id)
            ->orderBy('name')
            ->get();

        return $this->success(['data' => $profiles]);
    }

    /**
     * Elenco paginato delle subscription del tenant corrente.
     */
    public function subscriptions(Request $request): JsonResponse
    {
        $subscriptions = ParentalControlSubscription::where('tenant_id', $request->user()->tenant_id)
            ->with(['profile'])
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return $this->success($subscriptions);
    }

    /**
     * Attiva il Parental Control per un account PPPoE.
     */
    public function subscribe(ParentalControlSubscriptionRequest $request): JsonResponse
    {
        $subscription = $this->service->activateForAccount(
            customerId:      $request->validated('customer_id'),
            pppoeAccountId:  $request->validated('pppoe_account_id') ?? '',
            profileId:       $request->validated('profile_id'),
            tenantId:        $request->user()->tenant_id,
        );

        return $this->created(['data' => $subscription->load('profile')]);
    }

    /**
     * Aggiorna le liste custom del cliente (blacklist/whitelist).
     */
    public function update(UpdateParentalControlRequest $request, string $id): JsonResponse
    {
        $validated = $request->validated();

        $subscription = ParentalControlSubscription::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        $this->service->updateCustomerFilters(
            subscription: $subscription,
            blacklist:    $validated['blacklist'] ?? [],
            whitelist:    $validated['whitelist'] ?? [],
        );

        return $this->success(['data' => $subscription->fresh()]);
    }

    /**
     * Sospende (disattiva) una subscription di Parental Control.
     */
    public function unsubscribe(Request $request, string $id): JsonResponse
    {
        $subscription = ParentalControlSubscription::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        $this->service->suspendSubscription($subscription);

        return $this->success(['data' => $subscription->fresh()]);
    }

    /**
     * Statistiche DNS degli ultimi 30 giorni per una subscription.
     */
    public function stats(Request $request, string $id): JsonResponse
    {
        $subscription = ParentalControlSubscription::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        $stats = $this->resolver->getStats(
            subscription: $subscription,
            from:         now()->subDays(30),
            to:           now(),
        );

        return $this->success(['data' => $stats]);
    }
}
