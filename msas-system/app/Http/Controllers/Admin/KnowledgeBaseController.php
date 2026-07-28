<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeArticle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KnowledgeBaseController extends Controller
{
    public function index(): View
    {
        $articles = KnowledgeArticle::with('author')->latest()->paginate(20);
        return view('admin.knowledge.index', compact('articles'));
    }

    public function create(): View
    {
        $categories = KnowledgeArticle::$categories;
        return view('admin.knowledge.form', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['author_id']    = auth()->id();
        $data['slug']         = KnowledgeArticle::generateSlug($data['title']);
        $data['published_at'] = $data['is_published'] ? now() : null;

        KnowledgeArticle::create($data);

        return redirect()->route('admin.knowledge.index')->with('success', 'Article published.');
    }

    public function edit(KnowledgeArticle $knowledgeArticle): View
    {
        $categories = KnowledgeArticle::$categories;
        return view('admin.knowledge.form', ['article' => $knowledgeArticle, 'categories' => $categories]);
    }

    public function update(Request $request, KnowledgeArticle $knowledgeArticle): RedirectResponse
    {
        $data = $this->validated($request);

        if ($data['is_published'] && ! $knowledgeArticle->is_published) {
            $data['published_at'] = now();
        }

        $knowledgeArticle->update($data);

        return redirect()->route('admin.knowledge.index')->with('success', 'Article updated.');
    }

    public function destroy(KnowledgeArticle $knowledgeArticle): RedirectResponse
    {
        $knowledgeArticle->delete();
        return redirect()->route('admin.knowledge.index')->with('success', 'Article deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title'        => 'required|string|max:255',
            'excerpt'      => 'nullable|string|max:400',
            'body'         => 'required|string',
            'category'     => 'required|in:' . implode(',', array_keys(KnowledgeArticle::$categories)),
            'tags'         => 'nullable|string',
            'is_published' => 'boolean',
        ]) + ['is_published' => false, 'tags' => $this->parseTags($request->input('tags'))];
    }

    private function parseTags(?string $raw): array
    {
        if (! $raw) return [];
        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }
}
