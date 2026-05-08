<?php

use App\Models\Category;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Post;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\User;

it('lists published pages on the public JSON API', function () {
    $user = User::factory()->create();

    Page::query()->create([
        'user_id' => $user->id,
        'title' => 'Visible Page',
        'slug' => 'visible-page',
        'content' => '<p>Body</p>',
        'status' => 'published',
        'order' => 1,
    ]);

    Page::query()->create([
        'user_id' => $user->id,
        'title' => 'Draft Page',
        'slug' => 'draft-page',
        'content' => '<p>Hidden</p>',
        'status' => 'draft',
        'order' => 2,
    ]);

    $response = $this->getJson('/api/v1/pages');

    $response->assertOk();
    $response->assertJsonPath('data.0.slug', 'visible-page');
    $response->assertJsonMissingPath('data.1');
});

it('returns a single published page by slug with optional content', function () {
    $user = User::factory()->create();

    Page::query()->create([
        'user_id' => $user->id,
        'title' => 'About',
        'slug' => 'about',
        'content' => '<p>Secret</p>',
        'status' => 'published',
        'order' => 0,
    ]);

    $this->getJson('/api/v1/pages/about')
        ->assertOk()
        ->assertJsonMissingPath('data.content');

    $this->getJson('/api/v1/pages/about?include_content=1')
        ->assertOk()
        ->assertJsonPath('data.content', '<p>Secret</p>');
});

it('returns category tree with published posts_count', function () {
    $user = User::factory()->create();

    $root = Category::create([
        'name' => 'Root',
        'slug' => 'root',
    ]);

    $child = Category::create([
        'name' => 'Child',
        'slug' => 'child',
    ]);
    $child->appendToNode($root)->save();

    Post::factory()->create([
        'user_id' => $user->id,
        'category_id' => $child->id,
        'status' => 'published',
        'published_at' => now()->subHour(),
    ]);

    Post::factory()->create([
        'user_id' => $user->id,
        'category_id' => $child->id,
        'status' => 'draft',
        'published_at' => null,
    ]);

    $this->getJson('/api/v1/categories')
        ->assertOk()
        ->assertJsonPath('data.0.slug', 'root')
        ->assertJsonPath('data.0.children.0.slug', 'child')
        ->assertJsonPath('data.0.children.0.posts_count', 1);
});

it('lists tags with published posts_count', function () {
    $user = User::factory()->create();

    $tag = Tag::create([
        'name' => 'Laravel',
        'slug' => 'laravel',
    ]);

    $published = Post::factory()->create([
        'user_id' => $user->id,
        'status' => 'published',
        'published_at' => now()->subHour(),
    ]);

    $draft = Post::factory()->create([
        'user_id' => $user->id,
        'status' => 'draft',
        'published_at' => null,
    ]);

    $published->tags()->attach($tag->id);
    $draft->tags()->attach($tag->id);

    $this->getJson('/api/v1/tags')
        ->assertOk()
        ->assertJsonPath('data.0.slug', 'laravel')
        ->assertJsonPath('data.0.posts_count', 1);
});

it('returns active menus with nested items', function () {
    $menu = Menu::create([
        'name' => 'Main',
        'location' => 'main',
        'is_active' => true,
    ]);

    Menu::create([
        'name' => 'Inactive',
        'location' => 'inactive',
        'is_active' => false,
    ]);

    $parent = MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'Home',
        'url' => '/',
        'type' => 'home',
        'target' => '_self',
        'order' => 1,
    ]);

    MenuItem::create([
        'menu_id' => $menu->id,
        'parent_id' => $parent->id,
        'title' => 'Blog',
        'url' => '/blog',
        'type' => 'custom',
        'target' => '_self',
        'order' => 1,
    ]);

    $this->getJson('/api/v1/menus/main')
        ->assertOk()
        ->assertJsonPath('data.location', 'main')
        ->assertJsonPath('data.items.0.title', 'Home')
        ->assertJsonPath('data.items.0.children.0.title', 'Blog');
});

it('returns public settings only', function () {
    Setting::create([
        'group' => 'general',
        'key' => 'site_name',
        'value' => 'Nebula',
        'type' => 'string',
        'is_public' => true,
    ]);

    Setting::create([
        'group' => 'security',
        'key' => 'secret_key',
        'value' => 'do-not-expose',
        'type' => 'string',
        'is_public' => false,
    ]);

    $this->getJson('/api/v1/settings')
        ->assertOk()
        ->assertJsonPath('data.0.key', 'site_name')
        ->assertJsonMissing(['key' => 'secret_key']);
});

