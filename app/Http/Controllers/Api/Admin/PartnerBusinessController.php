<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\PartnerBusinessService;
use Exception;
use Illuminate\Http\Request;

class PartnerBusinessController extends Controller
{
    protected $partnerBusinessService;

    public function __construct(PartnerBusinessService $partnerBusinessService)
    {
        $this->partnerBusinessService = $partnerBusinessService;
    }

    public function getPartners(Request $request)
    {
        try {
            $types = $this->partnerBusinessService->getPartners($request->search, $request->per_page);
            return $this->sendResponse($types, 'Get partner fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    }
}
