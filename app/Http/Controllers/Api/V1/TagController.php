<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use App\Support\ContentSearch;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min((int) $request->query('per_page', 100), 100);

        $tags = Tag::query()
            ->withCount(['posts' => fn ($q) => $q->published()])
            ->when($request->filled('search'), function ($query) use ($request) {
                ContentSearch::applyLikeColumns($query, $request->string('search')->toString(), ['name', 'slug']);
            })
            ->orderBy('name')
            ->paginate($perPage);

        return TagResource::collection($tags);
    }
}

