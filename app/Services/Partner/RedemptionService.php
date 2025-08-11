<?php

namespace App\Services\Partner;

use App\Models\Redemption;
use App\Models\Reward;
use Carbon\Carbon;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Nette\Utils\Random;

class RedemptionService
{
    public function getRedeemHistory($searchCode)
    {
        $redeem_histories = Redemption::where('partner_id', Auth::id())
            ->when($searchCode, function ($query) use ($searchCode) {
                $query->where('code', 'like', '%' . $searchCode . '%');
            })
            ->latest()
            ->with([
                'user' => function ($q) {
                    $q->select('id', 'full_name', 'role', 'avatar');
                },
                'reward' => function ($q) {
                    $q->select('id', 'partner_id', 'title');
                }
            ])
            ->get();

        foreach ($redeem_histories as $history) {
            $history->user->avatar = $history->user->avatar
                ? asset($history->user->avatar)
                : 'https://ui-avatars.com/api/?background=random&name=' . urlencode($history->user->full_name);
        }

        $redemption_completed = Redemption::where('partner_id', Auth::id())
            ->where('status', 'Completed')
            ->count();

        $redemption_pending = Redemption::where('partner_id', Auth::id())
            ->where('status', 'Redeemed')
            ->count();

        return [
            'redemption_completed' => $redemption_completed,
            'redemption_pending' => $redemption_pending,
            'redeem_histories' => $redeem_histories,
        ];
    }
    public function getRedemptionDetails(int $id): ?Redemption
    {
        $details = Redemption::where('partner_id', Auth::id())
            ->latest()
            ->with([
                'user' => function ($q) {
                    $q->select('id', 'full_name', 'role', 'avatar');
                },
                'reward' => function ($q) {
                    $q->select('id', 'partner_id', 'title');
                }
            ])
            ->first();

        $details->user->avatar = $details->user->avatar
            ? asset($details->user->avatar)
            : 'https://ui-avatars.com/api/?background=random&name=' . urlencode($details->user->full_name);

        return $details;
    }

    public function markAsRedeemed(int $id)
{
    $redemption = Redemption::where('id', $id)
        ->where('partner_id', Auth::id())
        ->first();

    

    if (!$redemption) {
                return false;
            }

    $redemption->status = 'In progress';
    $redemption->save();

    return $redemption;
}

}