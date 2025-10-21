<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AddSubscriptionRequest;
use App\Http\Requests\Admin\EditSubscriptionRequest;
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


    public function addSubscription(AddSubscriptionRequest $request)
    {
        try {
            $reward = $this->subscriptionService->addSubscription($request->validated());

            return $this->sendResponse($reward, 'Subscription added successfully.');
        } catch (Exception $e) {
            return $this->sendError('Failed to add subscription.', [$e->getMessage()], 500);
        }
    }
    public function getSubscriptions(Request $request)
    {
        try {
            $types = $this->subscriptionService->getSubscriptions($request->search);
            return $this->sendResponse($types, 'Get subscriptions fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    }
    public function editSubscription(EditSubscriptionRequest $request, $id)
    {
        try {
            $challenge = $this->subscriptionService->editSubscription($id, $request->validated());
            return $this->sendResponse($challenge, 'Subscription updated successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    }

     public function deleteSubscription($id)
    {
        try {
            $deleted = $this->subscriptionService->deleteSubscription($id);
            if (!$deleted) {
                return $this->sendError('Subscription not found.', [], 404);
            }
            return $this->sendResponse([], 'Subscription deleted successfully.');
        } catch (Exception $e) {
            return $this->sendError('Failed to delete subscription.', [], 500);
        }
    }

}
