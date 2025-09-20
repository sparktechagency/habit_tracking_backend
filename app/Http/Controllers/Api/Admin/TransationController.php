<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\TransationService;
use Exception;
use Illuminate\Http\Request;

class TransationController extends Controller
{
    protected $transationService;

    public function __construct(TransationService $transationService)
    {
        $this->transationService = $transationService;
    }
    public function getTransations(Request $request)
    {
        try {
            $types = $this->transationService->getTransations($request->user_id,$request->per_page);
            return $this->sendResponse($types, 'Get all transations fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    } 

}
