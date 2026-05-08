<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PageResource;
use App\Models\Page;
use App\Support\ContentSearch;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min((int) $request->query('per_page', 50), 50);

        $pages = Page::query()
            ->where('status', 'published')
            ->when($request->filled('search'), function ($query) use ($request) {
                ContentSearch::applyToPageQuery($query, $request->string('search')->toString());
            })
            ->with(['user:id,name'])
            ->orderBy('order')
            ->orderBy('title')
            ->paginate($perPage);

        return PageResource::collection($pages);
    }

    public function show(Request $request, string $slug)
    {
        $page = Page::query()
            ->where('status', 'published')
            ->where('slug', $slug)
            ->with(['user:id,name'])
            ->firstOrFail();

        return new PageResource($page);
    }
}

