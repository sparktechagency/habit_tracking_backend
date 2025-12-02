<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StaticPageRequest;
use App\Models\StaticPage;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaticPageController extends Controller
{
    public function show(string $slug): JsonResponse
    {
        try {
            $page = StaticPage::bySlug($slug)->first();
            if (!$page || !$page->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Page not found',
                ], 404);
            }
            return $this->sendResponse($page, 'Get ' . $page->slug);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage(), [], 500);
        }
    }
    public function update(StaticPageRequest $request, string $slug)
    {
        $page = StaticPage::bySlug($slug)->first();
        if ($page) {
            $page->title = $request->validated()['title'];
            $page->content = $request->validated()['content'];
            $page->save();
            $message = $page->slug . ' Page updated successfully';
        } else {
            $page = StaticPage::create([
                'slug' => $slug,
                'title' => $request->validated()['title'],
                'content' => $request->validated()['content'],
            ]);
            $message = $page->slug . ' Page created successfully';
        }

        return $this->sendResponse($page, $message);
    }

}
