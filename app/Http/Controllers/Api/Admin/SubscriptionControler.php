<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\SubscriptionService;
use Exception;
use Illuminate\Http\Request;

class SubscriptionControler extends Controller
{
    protected $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    public function getSubscriptions(Request $request)
    {
        try {
            $types = $this->subscriptionService->getSubscriptions();
            return $this->sendResponse($types, 'Get subscriptions fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    }
    public function editPremiumPrice(Request $request, $id)
    {
        try {
            $challenge = $this->subscriptionService->editPremiumPrice($id, $request->price);
            return $this->sendResponse($challenge, 'Premium subscription price updated successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    }

}
