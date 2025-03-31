<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index()
    {
        return response()->json(Faq::all());
    }

    public function updateRating(Request $request, Faq $faq)
    {
        $faq->rating = $request->rating;
        $faq->save();

        return response()->json($faq);
    }
}
