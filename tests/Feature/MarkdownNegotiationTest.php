<?php

use App\Models\Post;
use App\Models\User;

it('returns HTML content by default when text/markdown is not accepted', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
});

it('returns Markdown content when Accept header is text/markdown', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create([
        'user_id' => $user->id,
        'title' => 'Test Post for Markdown Content',
        'slug' => 'test-post-markdown',
        'content' => '<h1>This is a heading</h1><p>This is a paragraph with a <a href="https://example.com">link</a>.</p>',
        'status' => 'published',
        'published_at' => now()->subHour(),
    ]);

    // Request the post details
    $response = $this->get('/blog/test-post-markdown', [
        'Accept' => 'text/markdown',
    ]);

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');
    $response->assertHeader('x-markdown-tokens');

    $content = $response->getContent();
    expect($content)->toContain('# This is a heading');
    expect($content)->toContain('This is a paragraph with a [link](https://example.com).');
});

it('strips visual noise such as script, style, nav, header, and footer tags', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create([
        'user_id' => $user->id,
        'title' => 'Noisy Post',
        'slug' => 'noisy-post',
        'content' => '<header><nav>Home Link</nav></header><article><h1>Real Content</h1><script>alert(1)</script><style>body { color: red; }</style><p>Paragraph text</p></article><footer>Footer Link</footer>',
        'status' => 'published',
        'published_at' => now()->subHour(),
    ]);

    $response = $this->get('/blog/noisy-post', [
        'Accept' => 'text/markdown',
    ]);

    $response->assertOk();
    $content = $response->getContent();

    // Verify it doesn't contain nav/header/footer content in output because they were stripped
    expect($content)->not->toContain('Home Link');
    expect($content)->not->toContain('Footer Link');
    expect($content)->not->toContain('alert(1)');
    expect($content)->not->toContain('color: red');

    // Verify it does contain the actual article content
    expect($content)->toContain('# Real Content');
    expect($content)->toContain('Paragraph text');
});

it('does not convert API routes or Inertia admin routes to Markdown', function () {
    $user = User::factory()->create();
    Post::factory()->create([
        'user_id' => $user->id,
        'title' => 'API Post',
        'slug' => 'api-post',
        'status' => 'published',
        'published_at' => now()->subHour(),
    ]);

    // Request the public API endpoint with Accept: text/markdown
    $response = $this->get('/api/v1/posts', [
        'Accept' => 'text/markdown',
    ]);

    $response->assertOk();
    // It should still be application/json since it's an API route
    $response->assertHeader('Content-Type', 'application/json');

    // Request admin login/auth route or an admin path
    $response = $this->get('/admin/login', [
        'Accept' => 'text/markdown',
    ]);

    // Should return Inertia redirection or HTML, but not text/markdown
    $response->assertHeaderMissing('x-markdown-tokens');
});
