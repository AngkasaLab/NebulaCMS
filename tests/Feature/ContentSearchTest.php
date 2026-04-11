<?php

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Support\ContentSearch;

it('finds posts by search on the public blog (LIKE path on sqlite)', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create();
    Post::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'title' => 'Alpha UniqueBlogFind 123',
        'slug' => 'alpha-post',
        'excerpt' => null,
        'content' => '<p>body</p>',
        'status' => 'published',
        'published_at' => now()->subHour(),
    ]);
    Post::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'title' => 'NoiseBlog Excluded',
        'slug' => 'noise-blog-excluded',
        'content' => '<p>x</p>',
        'status' => 'published',
        'published_at' => now()->subHour(),
    ]);

    $response = $this->get(route('blog', ['search' => 'UniqueBlogFind']));

    $response->assertOk();
    $response->assertSee('UniqueBlogFind', false);
    $response->assertDontSee('NoiseBlog Excluded', false);
});

it('filters the public JSON API by search parameter', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create();
    Post::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'title' => 'API Search Match',
        'slug' => 'api-search-match',
        'status' => 'published',
        'published_at' => now()->subHour(),
    ]);
    Post::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'title' => 'Noise',
        'slug' => 'noise',
        'status' => 'published',
        'published_at' => now()->subHour(),
    ]);

    $this->getJson('/api/v1/posts?search=API+Search')
        ->assertOk()
        ->assertJsonPath('data.0.slug', 'api-search-match')
        ->assertJsonMissingPath('data.1');
});

it('escapes LIKE wildcards in ContentSearch', function () {
    expect(ContentSearch::escapeLike('100%_off'))->toBe('100\%\_off');
});
