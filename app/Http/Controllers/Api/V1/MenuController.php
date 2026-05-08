<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\MenuResource;
use App\Models\Menu;

class MenuController extends Controller
{
    public function index()
    {
        $menus = Menu::query()
            ->where('is_active', true)
            ->with(['items.allChildren'])
            ->orderBy('name')
            ->get();

        return MenuResource::collection($menus);
    }

    public function show(string $location)
    {
        $menu = Menu::query()
            ->where('is_active', true)
            ->where('location', $location)
            ->with(['items.allChildren'])
            ->firstOrFail();

        return new MenuResource($menu);
    }
}

