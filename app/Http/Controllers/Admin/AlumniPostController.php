<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AlumniPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AlumniPostController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:manage alumni']);
    }

    public function index(Request $request)
    {
        $query = AlumniPost::query()->latest();
        if ($request->filled('search')) $query->where('title', 'like', '%'.$request->search.'%');
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('category')) $query->where('category', $request->category);
        $posts = $query->paginate(20)->withQueryString();
        return view('alumni.posts.index', compact('posts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required','string','max:255'],
            'body' => ['required','string'],
            'image' => ['nullable','image','max:4096'],
            'category' => ['required','in:announcement,news,opportunity,story,general'],
            'status' => ['required','in:draft,published'],
        ]);

        if ($request->hasFile('image')) $data['image'] = $request->file('image')->store('alumni/posts', 'public');
        $data['created_by'] = auth()->id();
        $data['postable_type'] = \App\Models\User::class;
        $data['postable_id'] = auth()->id();
        if ($data['status'] === 'published') {
            $data['approved_by'] = auth()->id();
            $data['approved_at'] = now();
            $data['published_at'] = now();
        }

        AlumniPost::create($data);
        return back()->with('success', 'Alumni post created successfully.');
    }

    public function update(Request $request, AlumniPost $alumniPost)
    {
        $data = $request->validate([
            'title' => ['required','string','max:255'],
            'body' => ['required','string'],
            'image' => ['nullable','image','max:4096'],
            'category' => ['required','in:announcement,news,opportunity,story,general'],
            'status' => ['required','in:draft,pending,published,rejected,archived'],
        ]);

        if ($request->hasFile('image')) {
            if ($alumniPost->image) Storage::disk('public')->delete($alumniPost->image);
            $data['image'] = $request->file('image')->store('alumni/posts', 'public');
        }
        if ($data['status'] === 'published' && !$alumniPost->published_at) {
            $data['approved_by'] = auth()->id();
            $data['approved_at'] = now();
            $data['published_at'] = now();
        }

        $alumniPost->update($data);
        return back()->with('success', 'Alumni post updated successfully.');
    }

    public function approve(AlumniPost $alumniPost)
    {
        $alumniPost->update([
            'status' => 'published',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'published_at' => now(),
        ]);
        return back()->with('success', 'Alumni post approved and published.');
    }

    public function reject(Request $request, AlumniPost $alumniPost)
    {
        $alumniPost->update(['status' => 'rejected']);
        return back()->with('success', 'Alumni post rejected.');
    }

    public function destroy(AlumniPost $alumniPost)
    {
        if ($alumniPost->image) Storage::disk('public')->delete($alumniPost->image);
        $alumniPost->delete();
        return back()->with('success', 'Alumni post deleted successfully.');
    }
}
