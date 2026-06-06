<?php

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Tests\TestCase;

beforeEach(function () {
    /** @var TestCase $this */
    $this->seed(RoleAndPermissionSeeder::class);
    Category::factory()->create();
});

test('autosave response includes preview links for newly created draft', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $user->assignRole('Author');

    $response = $this->actingAs($user)->postJson(route('admin.posts.autosave', absolute: false), [
        'title' => 'Hello',
        'content' => '<p>World</p>',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'post_id',
            'saved_at',
            'preview' => ['url', 'signed_url'],
        ]);

    $postId = $response->json('post_id');
    expect($postId)->toBeInt();
    expect(Post::query()->whereKey($postId)->exists())->toBeTrue();

    $previewUrl = (string) $response->json('preview.url');
    $signedUrl = (string) $response->json('preview.signed_url');

    expect($previewUrl)->toContain('/preview/post/'.$postId);
    expect($signedUrl)->toContain('/preview/post/'.$postId);
    expect($signedUrl)->toContain('signature=');
});
