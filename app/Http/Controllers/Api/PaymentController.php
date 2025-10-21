<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Transaction;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class PaymentController extends Controller
{
    public function paymentIntent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric',
            'payment_method_types' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        Stripe::setApiKey(env('STRIPE_SECRET'));

        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => $request->amount * 100, // cents
                'currency' => 'usd',
                'payment_method_types' => [$request->payment_method_types], // example: 'card'
                'metadata' => [
                    'user_id' => Auth::id(),
                ],
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Payment intent successfully created',
                'data' => $paymentIntent,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function paymentSuccess(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_intent_id' => 'required',
            'card_number' => 'nullable',
            'subscription_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        Stripe::setApiKey(env('STRIPE_SECRET'));

        try {
            $paymentIntent = PaymentIntent::retrieve($request->payment_intent_id);
            if ($paymentIntent->status === 'succeeded') {  // succeeded or requires_payment_method

                $subscription = Subscription::where('id', $request->subscription_id)->first();

                $plan = Plan::create([
                    'user_id' => Auth::id(),
                    'plan_name' => $subscription->plan_name,
                    'duration' => $subscription->duration,
                    'price' => $subscription->price,
                    'features' => json_encode($subscription->features),
                    'renewal' => $subscription->duration == 'Monthly' ? Carbon::now()->addMonth() : Carbon::now()->addYear(),
                ]);

                $transaction = Transaction::Create([
                    'payment_intent_id' => $request->payment_intent_id,
                    'card_number' => $request->card_number,
                    'user_name' => Auth::user()->full_name,
                    'plan_name' => Subscription::where('id', $request->subscription_id)->first()->plan_name,
                    'date' => Carbon::now(),
                    'amount' => Subscription::where('id', $request->subscription_id)->first()->price,
                    'status' => 'Completed'
                ]);

                $plan->features = json_decode($plan->features);

                return response()->json([
                    'status' => true,
                    'message' => 'Payment done and plan created successfully',
                    'data' => [
                        'plan' => $plan,
                        'transaction' => $transaction,
                    ],
                ], 200);

            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Payment failed. Status: ' . $paymentIntent->status,
                ], 400);
            }

        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Payment failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
