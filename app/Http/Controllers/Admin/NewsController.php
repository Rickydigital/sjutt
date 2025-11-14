<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class NewsController extends Controller
{
      // app/Http/Controllers/NewsController.php
public function index(Request $request)
{
    $search = $request->query('search');
    $filterCreator = $request->query('filter_creator');

    $news = News::with('user')
        ->when($search, fn($q) => $q->where('title', 'like', "%{$search}%")
                                   ->orWhere('description', 'like', "%{$search}%"))
        ->when($filterCreator, fn($q, $id) => $q->where('created_by', $id))
        ->latest()
        ->paginate(10);

    return view('admin.news.index', compact('news'));
}

    public function create()
    {
        return view('admin.news.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'description' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg',
            'video' => 'nullable|mimes:mp4,avi,mov',
        ]);

        $news = new News($request->only(['title', 'description']));
        if ($request->file('image')) {
            $news->image = $request->file('image')->store('news_images', 'public');
        }
        if ($request->file('video')) {
            $news->video = $request->file('video')->store('news_videos', 'public');
        }
        $news->created_by = Auth::id();
        $news->save();

        return redirect()->route('news.index')->with('success', 'News added successfully');
    }

    public function edit(News $news)
    {
        return view('admin.news.edit', compact('news'));
    }

    public function update(Request $request, News $news)
    {
        $request->validate([
            'title' => 'required',
            'description' => 'required',
        ]);

        if ($request->hasFile('image')) {
            if ($news->image) Storage::disk('public')->delete($news->image);
            $news->image = $request->file('image')->store('news_images', 'public');
        }

        if ($request->hasFile('video')) {
            if ($news->video) Storage::disk('public')->delete($news->video);
            $news->video = $request->file('video')->store('news_videos', 'public');
        }

        $news->update($request->only(['title', 'description']));

        return redirect()->route('news.index')->with('success', 'News updated successfully');
    }

    public function destroy(News $news)
    {
        if ($news->image) Storage::disk('public')->delete($news->image);
        if ($news->video) Storage::disk('public')->delete($news->video);
        $news->delete();
        return redirect()->route('news.index')->with('success', 'News deleted');
    }
}

