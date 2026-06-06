<?php

use App\Models\Media;
use App\Models\MediaFolder;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Str;
use Tests\TestCase;

beforeEach(function () {
    /** @var TestCase $this */
    $this->seed(RoleAndPermissionSeeder::class);
});

test('users without view media permission cannot access media picker endpoint', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson(route('admin.media.picker', absolute: false))
        ->assertForbidden();
});

test('media picker returns folders and paginated media within current folder', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $user->assignRole('Editor');

    $folderA = MediaFolder::create(['name' => 'Folder A']);
    $folderB = MediaFolder::create(['name' => 'Folder B', 'parent_id' => $folderA->id]);

    $mediaInA = Media::create([
        'folder_id' => $folderA->id,
        'user_id' => $user->id,
        'name' => 'a.png',
        'file_name' => 'a.png',
        'mime_type' => 'image/png',
        'path' => 'uploads/a.png',
        'disk' => 'public',
        'file_hash' => Str::random(32),
        'size' => 1234,
        'custom_properties' => [],
    ]);

    $mediaInB = Media::create([
        'folder_id' => $folderB->id,
        'user_id' => $user->id,
        'name' => 'b.png',
        'file_name' => 'b.png',
        'mime_type' => 'image/png',
        'path' => 'uploads/b.png',
        'disk' => 'public',
        'file_hash' => Str::random(32),
        'size' => 1234,
        'custom_properties' => [],
    ]);

    $response = $this->actingAs($user)->getJson(route('admin.media.picker', [
        'folder_id' => $folderA->id,
        'type' => 'image',
        'per_page' => 24,
    ], absolute: false));

    $response->assertOk()
        ->assertJsonStructure([
            'folders',
            'media' => [
                'data',
                'current_page',
                'last_page',
                'next_page_url',
                'prev_page_url',
                'per_page',
                'total',
            ],
        ]);

    $folderIds = collect($response->json('folders'))->pluck('id')->all();
    expect($folderIds)->toContain($folderB->id);

    $mediaIds = collect($response->json('media.data'))->pluck('id')->all();
    expect($mediaIds)->toContain($mediaInA->id);
    expect($mediaIds)->not->toContain($mediaInB->id);
});

test('media picker search filters media by name', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $user->assignRole('Editor');

    $folder = MediaFolder::create(['name' => 'Folder']);

    $cat = Media::create([
        'folder_id' => $folder->id,
        'user_id' => $user->id,
        'name' => 'cat.png',
        'file_name' => 'cat.png',
        'mime_type' => 'image/png',
        'path' => 'uploads/cat.png',
        'disk' => 'public',
        'file_hash' => Str::random(32),
        'size' => 1234,
        'custom_properties' => [],
    ]);

    $dog = Media::create([
        'folder_id' => $folder->id,
        'user_id' => $user->id,
        'name' => 'dog.png',
        'file_name' => 'dog.png',
        'mime_type' => 'image/png',
        'path' => 'uploads/dog.png',
        'disk' => 'public',
        'file_hash' => Str::random(32),
        'size' => 1234,
        'custom_properties' => [],
    ]);

    $response = $this->actingAs($user)->getJson(route('admin.media.picker', [
        'folder_id' => $folder->id,
        'type' => 'image',
        'search' => 'cat',
        'per_page' => 24,
    ], absolute: false));

    $mediaIds = collect($response->json('media.data'))->pluck('id')->all();
    expect($mediaIds)->toContain($cat->id);
    expect($mediaIds)->not->toContain($dog->id);
});
