@extends('layouts.admin')

@section('content')
    <h1>Edit FAQ</h1>
    <form action="{{ route('admin.faqs.update', $faq->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="question">Question</label>
            <input type="text" name="question" id="question" class="form-control" value="{{ $faq->question }}" required>
        </div>
        <div class="form-group">
            <label for="answer">Answer</label>
            <textarea name="answer" id="answer" class="form-control" required>{{ $faq->answer }}</textarea>
        </div>
        <div class="form-group">
            <label for="rating">Rating (optional)</label>
            <input type="number" name="rating" id="rating" class="form-control" value="{{ $faq->rating }}">
        </div>
        <button type="submit" class="btn btn-success">Update FAQ</button>
    </form>
@endsection
