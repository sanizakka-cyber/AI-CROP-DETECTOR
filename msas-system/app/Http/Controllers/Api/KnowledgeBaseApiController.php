<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeArticle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KnowledgeBaseApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = KnowledgeArticle::published()->latest('published_at');
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
        $articles = $query->select(['id','title','slug','excerpt','category','tags','view_count','published_at'])
            ->paginate(15);
        return response()->json(['articles' => $articles, 'categories' => KnowledgeArticle::$categories]);
    }

    public function show(string $slug): JsonResponse
    {
        $article = KnowledgeArticle::published()->where('slug', $slug)->firstOrFail();
        $article->increment('view_count');
        $related = KnowledgeArticle::published()
            ->where('category', $article->category)
            ->where('id', '!=', $article->id)
            ->select(['id','title','slug','excerpt','published_at'])
            ->latest('published_at')
            ->take(4)
            ->get();
        return response()->json(['article' => $article, 'related' => $related]);
    }
}