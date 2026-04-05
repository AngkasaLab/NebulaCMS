<?php

use App\Services\UpdateService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

uses(Tests\TestCase::class);

beforeEach(function () {
    Cache::forget(UpdateService::CACHE_KEY);
});

describe('UpdateService::getSharedUpdateAvailability', function () {
    it('returns null when cache is empty', function () {
        expect(app(UpdateService::class)->getSharedUpdateAvailability())->toBeNull();
    });

    it('returns null and clears cache when cached latest is not newer than installed', function () {
        Config::set('nebula.version', '1.1.0');
        Cache::put(UpdateService::CACHE_KEY, [
            'available' => true,
            'current' => '1.0.0',
            'latest' => '1.1.0',
            'download_url' => 'https://example.com/x.zip',
        ], now()->addHour());

        expect(app(UpdateService::class)->getSharedUpdateAvailability())->toBeNull();
        expect(Cache::get(UpdateService::CACHE_KEY))->toBeNull();
    });

    it('returns merged payload when latest is newer than installed', function () {
        Config::set('nebula.version', '1.0.0');
        Cache::put(UpdateService::CACHE_KEY, [
            'available' => true,
            'current' => '1.0.0',
            'latest' => '1.1.0',
            'download_url' => 'https://example.com/x.zip',
        ], now()->addHour());

        $result = app(UpdateService::class)->getSharedUpdateAvailability();

        expect($result)->not->toBeNull();
        expect($result['available'])->toBeTrue();
        expect($result['current'])->toBe('1.0.0');
        expect($result['latest'])->toBe('1.1.0');
    });

    it('returns null when cached available is false', function () {
        Config::set('nebula.version', '1.0.0');
        Cache::put(UpdateService::CACHE_KEY, [
            'available' => false,
            'current' => '1.0.0',
        ], now()->addHour());

        expect(app(UpdateService::class)->getSharedUpdateAvailability())->toBeNull();
    });
});

describe('UpdateService::checkForUpdate', function () {
    it('reports available when GitHub latest is newer', function () {
        Config::set('nebula.version', '1.0.0');
        Config::set('nebula.github_repo', 'test/repo');

        Http::fake([
            'api.github.com/repos/test/repo/releases/latest' => Http::response([
                'tag_name' => 'v1.1.0',
                'html_url' => 'https://github.com/test/repo/releases/tag/v1.1.0',
                'body' => 'See https://example.com/changelog',
                'published_at' => '2026-01-01T00:00:00Z',
                'assets' => [
                    ['name' => 'release.zip', 'browser_download_url' => 'https://github.com/dl.zip'],
                ],
            ], 200),
        ]);

        $result = app(UpdateService::class)->checkForUpdate();

        expect($result['available'])->toBeTrue();
        expect($result['latest'])->toBe('1.1.0');
        expect($result['download_url'])->toBe('https://github.com/dl.zip');
        expect($result['release_url'])->toBe('https://github.com/test/repo/releases/tag/v1.1.0');
        expect($result['release_notes'])->toContain('changelog');
    });

    it('reports not available when already on latest', function () {
        Config::set('nebula.version', '1.1.0');
        Config::set('nebula.github_repo', 'test/repo');

        Http::fake([
            'api.github.com/repos/test/repo/releases/latest' => Http::response([
                'tag_name' => 'v1.1.0',
                'body' => '',
                'published_at' => null,
                'assets' => [],
            ], 200),
        ]);

        $result = app(UpdateService::class)->checkForUpdate();

        expect($result['available'])->toBeFalse();
    });
});

describe('UpdateService::getLatestReleaseZipInfo', function () {
    beforeEach(function () {
        Cache::flush();
    });

    it('returns zip URL and version from latest release assets', function () {
        Config::set('nebula.github_repo', 'owner/cms');

        Http::fake([
            'api.github.com/repos/owner/cms/releases/latest' => Http::response([
                'tag_name' => 'v2.0.0',
                'html_url' => 'https://github.com/owner/cms/releases/tag/v2.0.0',
                'published_at' => '2026-04-01T12:00:00Z',
                'assets' => [
                    ['name' => 'nebulacms-v2.0.0.zip', 'browser_download_url' => 'https://github.com/assets/z.zip'],
                ],
            ], 200),
        ]);

        $info = app(UpdateService::class)->getLatestReleaseZipInfo();

        expect($info['version'])->toBe('2.0.0');
        expect($info['tag'])->toBe('v2.0.0');
        expect($info['download_url'])->toBe('https://github.com/assets/z.zip');
        expect($info['releases_url'])->toBe('https://github.com/owner/cms/releases');
        expect($info['error'])->toBeNull();
    });

    it('returns error payload when API fails', function () {
        Config::set('nebula.github_repo', 'owner/cms');

        Http::fake([
            'api.github.com/repos/owner/cms/releases/latest' => Http::response([], 500),
        ]);

        $info = app(UpdateService::class)->getLatestReleaseZipInfo();

        expect($info['download_url'])->toBeNull();
        expect($info['error'])->toContain('500');
        expect($info['releases_url'])->toBe('https://github.com/owner/cms/releases');
    });
});
