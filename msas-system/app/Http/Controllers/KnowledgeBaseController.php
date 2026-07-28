<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeArticle;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KnowledgeBaseController extends Controller
{
    public function index(Request $request): View
    {
        $query = KnowledgeArticle::published()->with('author')->latest('published_at');

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(fn($s) =>
                $s->where('title', 'like', "%{$q}%")
                  ->orWhere('excerpt', 'like', "%{$q}%")
                  ->orWhere('body', 'like', "%{$q}%")
            );
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $articles   = $query->paginate(12)->withQueryString();
        $categories = KnowledgeArticle::$categories;

        return view('knowledge.index', compact('articles', 'categories'));
    }

    public function show(string $slug): View
    {
        $article = KnowledgeArticle::published()->where('slug', $slug)->firstOrFail();
        $article->increment('view_count');

        $related = KnowledgeArticle::published()
            ->where('category', $article->category)
            ->where('id', '!=', $article->id)
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('knowledge.show', compact('article', 'related'));
    }
}
