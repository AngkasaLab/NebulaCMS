@extends('theme::layouts.app')

@section('title', 'Home')

@section('content')
    <div class="container">
        <!-- Hero Section -->
        <div class="py-5 text-center">
            <h1 class="display-4">Welcome to {{ config('app.name') }}</h1>
            <p class="lead">Platform konten modern yang dibangun dengan Laravel dan React.</p>
        </div>

        <!-- Latest Posts Section -->
        <section class="mb-5">
            <h2 class="mb-4">Latest Posts</h2>

            <div class="row g-4">
                @foreach ($posts as $post)
                    <div class="col-md-4">
                        <div class="card h-100">
                            @if ($post->featured_image_url)
                                <img src="{{ $post->featured_image_url }}" class="card-img-top" alt="{{ $post->title }}">
                            @endif

                            <div class="card-body">
                                <h5 class="card-title">{{ $post->title }}</h5>
                                <p class="card-text text-muted">
                                    {{ Str::limit(strip_tags($post->content), 100) }}
                                </p>

                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="btn-group">
                                        <a href="{{ route('blog.post', $post->slug) }}"
                                            class="btn btn-sm btn-outline-primary">Read More</a>
                                    </div>
                                    <small class="text-muted">{{ $post->published_at?->diffForHumans() }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- Categories & Tags Section -->
        <div class="row">
            <!-- Categories -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Categories</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            @foreach ($categories as $category)
                                <a href="{{ route('blog', ['category' => $category->slug]) }}"
                                    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    {{ $category->name }}
                                    <span class="badge bg-primary rounded-pill">{{ $category->posts_count }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tags -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Tags</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($tags as $tag)
                                <a href="{{ route('blog', ['tag' => $tag->slug]) }}"
                                    class="btn btn-outline-secondary btn-sm">
                                    {{ $tag->name }} ({{ $tag->posts_count }})
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
