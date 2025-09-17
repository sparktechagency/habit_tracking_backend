<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SettingsRequest;
use App\Services\SettingsService;
use Exception;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    protected $settingsService;
    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }
    public function editProfile(SettingsRequest $request)
    {
        try {
            $result = $this->settingsService->editProfile($request->validated());

            if (!$result['success']) {
                return $this->sendError($result['message'] ?? 'Something went wrong', [], $result['code'] ?? 500);
            }

            return $this->sendResponse(
                ['user' => $result['data']],
                'Your profile updated successfully'
            );
        } catch (Exception $e) {
            return $this->sendError($e->getMessage(), [], 500);
        }
    }

}
