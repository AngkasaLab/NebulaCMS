<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::query()
            ->withCount(['posts' => fn ($q) => $q->published()])
            ->defaultOrder()
            ->get()
            ->toTree();

        return CategoryResource::collection($categories);
    }
}

