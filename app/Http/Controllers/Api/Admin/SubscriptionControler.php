<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AddSubscriptionRequest;
use App\Http\Requests\Admin\EditSubscriptionRequest;
use App\Http\Requests\Admin\FreeSubscriptionBuying\AddFreeSubscriptionRequest;
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
            return $this->sendError('Something went wrong.', [$e->getMessage()], 500);
        }
    }
    public function deleteSubscription($id)
    {
        try {
            $deleted = $this->subscriptionService->deleteSubscription($id);
            return $this->sendResponse([], 'Subscription deleted successfully.');
        } catch (Exception $e) {
            return $this->sendError('Failed to delete subscription.', [$e->getMessage()], 500);
        }
    }

    // =======================free subscription buying==============================
    public function addFreeSubscription(AddFreeSubscriptionRequest $request)
    {
        try {
            $reward = $this->subscriptionService->addFreeSubscription($request->validated());

            return $this->sendResponse($reward, 'Free subscription added successfully.');
        } catch (Exception $e) {
            return $this->sendError('Failed to add free subscription.', [$e->getMessage()], 500);
        }
    }

    public function getFreeSubscriptions()
    {
        try {
            $types = $this->subscriptionService->getFreeSubscriptions();
            return $this->sendResponse($types, 'Get free subscriptions fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Failed to get free subscriptions.', [$e->getMessage()], 500);
        }
    }

    public function viewFreeSubscription($id)
    {
        try {
            $types = $this->subscriptionService->viewFreeSubscription($id);
            return $this->sendResponse($types, 'View free subscription fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Failed to view free subscription.', [$e->getMessage()], 500);
        }
    }

    public function removeFreeSubscription($id)
    {
        try {
            $types = $this->subscriptionService->removeFreeSubscription($id);
            return $this->sendResponse($types, 'Free subscription removed successfully.');
        } catch (Exception $e) {
            return $this->sendError('Failed to removed free subscription.', [$e->getMessage()], 500);
        }
    }

    public function renewFreeSubscription(Request $request, $id)
    {
        try {
            $types = $this->subscriptionService->renewFreeSubscription($id, $request->duration);
            return $this->sendResponse($types, 'Free subscription renew successfully.');
        } catch (Exception $e) {
            return $this->sendError('Failed to renew free subscription.', [$e->getMessage()], 500);
        }
    }


    // =======================refund==============================
    public function getPlans(Request $request)
    {
        try {
            $reward = $this->subscriptionService->getPlans($request->search);

            return $this->sendResponse($reward, 'Get plans fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Failed to get plans fetched.', [$e->getMessage()], 500);
        }
    }

    public function viewPlan($id)
    {
        try {
            $reward = $this->subscriptionService->viewPlan($id);

            return $this->sendResponse($reward, 'Get plan fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Failed to get plan fetched.', [$e->getMessage()], 500);
        }
    }

    public function refund(Request $request)
    {
        try {
            $reward = $this->subscriptionService->refund($request->storeTransactionId);

            return $this->sendResponse($reward, 'Refund successfully.');
        } catch (Exception $e) {
            return $this->sendError('Failed to refund.', [$e->getMessage()], 500);
        }
    }
}
