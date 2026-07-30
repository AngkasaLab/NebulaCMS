@extends('theme::layouts.app')

@section('title', 'Blog')

@section('content')
    <div class="container">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Search Results Info -->
                @if (request('search') || request('category') || request('tag'))
                    <div class="alert alert-info mb-4">
                        Showing results for:
                        @if (request('search'))
                            <strong>Search: "{{ request('search') }}"</strong>
                        @endif
                        @if (request('category'))
                            <strong>Category: "{{ $categories->firstWhere('slug', request('category'))->name }}"</strong>
                        @endif
                        @if (request('tag'))
                            <strong>Tag: "{{ $tags->firstWhere('slug', request('tag'))->name }}"</strong>
                        @endif
                        <a href="{{ route('blog') }}" class="float-end">Clear filters</a>
                    </div>
                @endif

                <!-- Posts List -->
                <div class="row g-4">
                    @forelse($posts as $post)
                        <div class="col-md-6">
                            <div class="card h-100">
                                @if ($post->featured_image)
                                    <img src="{{ asset('storage/' . $post->featured_image) }}" class="card-img-top"
                                        alt="{{ $post->title }}">
                                @endif

                                <div class="card-body">
                                    <h5 class="card-title">{{ $post->title }}</h5>
                                    <p class="card-text text-muted">
                                        {{ Str::limit(strip_tags($post->content), 100) }}
                                    </p>

                                    <!-- Post Meta -->
                                    <div class="mb-3">
                                        @if ($post->category)
                                            <a href="{{ route('blog', ['category' => $post->category->slug]) }}"
                                                class="badge bg-primary text-decoration-none">
                                                {{ $post->category->name }}
                                            </a>
                                        @endif

                                        @foreach ($post->tags as $tag)
                                            <a href="{{ route('blog', ['tag' => $tag->slug]) }}"
                                                class="badge bg-secondary text-decoration-none">
                                                {{ $tag->name }}
                                            </a>
                                        @endforeach
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <a href="{{ route('blog.post', $post->slug) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            Read More
                                        </a>
                                        <small class="text-muted">
                                            {{ $post->published_at?->diffForHumans() }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="alert alert-info">
                                No posts found.
                            </div>
                        </div>
                    @endforelse
                </div>

                <!-- Pagination -->
                <div class="mt-4">
                    {{ $posts->links() }}
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Search -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form action="{{ route('blog') }}" method="GET">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Search posts..." name="search"
                                    value="{{ request('search') }}">
                                <button class="btn btn-primary" type="submit">Search</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Categories -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Categories</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            @foreach ($categories as $category)
                                <a href="{{ route('blog', ['category' => $category->slug]) }}"
                                    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    {{ $category->name }}
                                    <span class="badge bg-primary rounded-pill">
                                        {{ $category->posts_count }}
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Tags -->
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
