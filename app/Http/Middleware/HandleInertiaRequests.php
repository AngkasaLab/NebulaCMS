<?php

namespace App\Http\Middleware;

use App\Models\Plugin;
use App\Support\PluginHooks;
use App\Services\UpdateService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'locale' => app()->getLocale(),
            'translations' => [
                'posts' => trans('posts'),
                'common' => trans('common'),
            ],
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->user(),
                'permissions' => $request->user()
                    ? $request->user()->getAllPermissions()->pluck('name')->values()->all()
                    : [],
            ],
            'ziggy' => fn (): array => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'sidebarOpen' => $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'updateCheck' => fn () => $request->session()->get('updateCheck'),
            ],
            'updateAvailable' => fn () => app(UpdateService::class)->getSharedUpdateAvailability(),
            'adminNavigation' => fn () => $this->buildAdminNavigation($request),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildAdminNavigation(Request $request): array
    {
        /** @var Collection<int, Plugin> $plugins */
        $plugins = app()->bound('plugins') ? app('plugins') : collect();

        $items = $plugins
            ->flatMap(fn (Plugin $plugin) => $plugin->getAdminNavigationFromDisk())
            ->values()
            ->all();

        $items = apply_filters(PluginHooks::ADMIN_NAVIGATION, $items, $request);

        if (! is_array($items)) {
            return [];
        }

        return array_values(array_filter(
            array_map([$this, 'normalizeAdminNavigationItem'], $items),
            fn (?array $item) => $item !== null
        ));
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>|null
     */
    protected function normalizeAdminNavigationItem(array $item): ?array
    {
        $title = $item['title'] ?? null;
        $href = $item['href'] ?? null;

        if (! is_string($title) || $title === '' || ! is_string($href) || $href === '') {
            return null;
        }

        $group = $item['group'] ?? 'admin';
        if (! is_string($group) || ! in_array($group, ['main', 'content', 'admin'], true)) {
            $group = 'admin';
        }

        $children = [];
        foreach ($item['items'] ?? [] as $child) {
            if (! is_array($child)) {
                continue;
            }

            $childTitle = $child['title'] ?? null;
            $childHref = $child['href'] ?? null;
            if (! is_string($childTitle) || $childTitle === '' || ! is_string($childHref) || $childHref === '') {
                continue;
            }

            $children[] = [
                'title' => $childTitle,
                'href' => $childHref,
            ];
        }

        $matchPaths = array_values(array_filter(
            is_array($item['match_paths'] ?? null) ? $item['match_paths'] : [],
            fn ($path) => is_string($path) && $path !== ''
        ));

        return [
            'title' => $title,
            'href' => $href,
            'group' => $group,
            'badge' => is_string($item['badge'] ?? null) || is_bool($item['badge'] ?? null) ? $item['badge'] : null,
            'icon' => is_string($item['icon'] ?? null) ? $item['icon'] : null,
            'items' => $children,
            'match_paths' => $matchPaths,
        ];
    }
}
