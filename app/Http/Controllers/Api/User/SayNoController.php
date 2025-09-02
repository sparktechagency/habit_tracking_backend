<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\AddEntryRequest;
use App\Services\User\SayNoService;
use Exception;
use Illuminate\Http\Request;

class SayNoController extends Controller
{
    protected $sayNoService;

    public function __construct(SayNoService $sayNoService)
    {
        $this->sayNoService = $sayNoService;
    }

    public function addEntry(AddEntryRequest $request)
    {
        try {
            $entry = $this->sayNoService->addEntry($request->validated());
            return $this->sendResponse($entry, 'Entry added successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    }
    public function getEntries(Request $request)
    {
        try {
            $entries = $this->sayNoService->getEntries($request->filter);
            return $this->sendResponse($entries, 'Entries fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    }
    public function viewEntry($id)
    {
        try {
            $entry = $this->sayNoService->viewEntry($id);
            if (!$entry) {
                return $this->sendError('Entry not found.', [], 404);
            }
            return $this->sendResponse($entry, 'Entry fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    }
    public function deleteEntry($id)
    {
        try {
            $deleted = $this->sayNoService->deleteEntry($id);
            if (!$deleted) {
                return $this->sendError('Entry not found or unauthorized.', [], 404);
            }
            return $this->sendResponse([], 'Entry deleted successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    }
}
