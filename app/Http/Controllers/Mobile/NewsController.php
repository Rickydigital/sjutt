<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class NewsController extends Controller
{
    public function index()
    {
        try {
            $news = News::with(['user', 'reactions', 'comments'])->latest()->get();
            return response()->json([
                'success' => true,
                'message' => 'News fetched successfully',
                'data' => $news
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch news',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $news = News::with(['user', 'reactions', 'comments'])->findOrFail($id);
            return response()->json([
                'success' => true,
                'message' => 'News retrieved successfully',
                'data' => $news
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'News not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

