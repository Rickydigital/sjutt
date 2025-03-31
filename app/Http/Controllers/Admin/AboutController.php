<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\About;
use Illuminate\Http\Request;

class AboutController extends Controller
{
    
    public function index(Request $request)
    {
        $search = $request->query('search');
        $abouts = About::when($search, function ($query, $search) {
            return $query->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
        })->paginate(10);

        return view('admin.abouts.index', compact('abouts'));
    }

    public function create()
    {
        return view('admin.abouts.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
        ]);

        About::create($request->all());

        return redirect()->route('about.index');
    }

    public function edit(About $about)
    {
        return view('admin.abouts.edit', compact('about'));
    }

    public function update(Request $request, About $about)
    {
        $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
        ]);

        $about->update($request->all());

        return redirect()->route('about.index');
    }

    public function destroy(About $about)
    {
        $about->delete();
        return redirect()->route('about.index');
    }
}
