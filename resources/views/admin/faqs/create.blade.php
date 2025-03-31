@extends('layouts.admin')

@section('content')
    <h1>Create FAQ</h1>
    <form action="{{ route('faqs.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="question">Question</label>
            <input type="text" name="question" id="question" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="answer">Answer</label>
            <textarea name="answer" id="answer" class="form-control" required></textarea>
        </div>
        <div class="form-group">
            <label for="rating">Rating (optional)</label>
            <input type="number" name="rating" id="rating" class="form-control">
        </div>
        <button type="submit" class="btn btn-success">Create FAQ</button>
    </form>
@endsection
