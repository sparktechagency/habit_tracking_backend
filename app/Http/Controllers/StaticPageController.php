<?php

namespace App\Http\Controllers;

use App\Http\Requests\StaticPageRequest;
use App\Models\StaticPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaticPageController extends Controller
{
    public function show(string $slug): JsonResponse
    {
        $page = StaticPage::bySlug($slug)->first();

        if (!$page || !$page->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'slug' => $page->slug,
                'title' => $page->title,
                'content' => $page->content,
            ],
        ]);
    }

    public function update(StaticPageRequest $request, string $slug)
    {
        $page = StaticPage::bySlug($slug)->first();

        if ($page) {
            // update হলে
            $page->update($request->only(['title', 'content', 'is_active']));

            $message = 'Page updated successfully';
        } else {
            // নতুন create হলে
            $page = StaticPage::create([
                'slug' => $slug,
                'title' => $request->title,
                'content' => $request->content,
                'is_active' => $request->is_active ?? true, // default active
            ]);

            $message = 'Page created successfully';
        }

        return response()->json([
            'success' => true,
            'data' => $page,
            'message' => $message,
        ]);
    }

}
