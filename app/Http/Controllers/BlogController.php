<?php

namespace App\Http\Controllers;

use App\Models\Post;

class BlogController extends Controller
{
    public function index()
    {
        $posts = Post::with(['category', 'author'])
            ->published()
            ->lang('en')
            ->orderByDesc('published_at')
            ->paginate(12);

        return view('blog.index', compact('posts'));
    }

    public function show(string $slug)
    {
        $post = Post::with(['category', 'author', 'tags', 'faqs'])
            ->where('slug', $slug)
            ->published()
            ->firstOrFail();

        // Increment view count without triggering updated_at
        Post::where('id', $post->id)->increment('view_count');

        return view('blog.show', compact('post'));
    }
}
