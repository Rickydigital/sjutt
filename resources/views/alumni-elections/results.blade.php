@extends('components.app-main-layout')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header bg-success text-white d-flex justify-content-between"><h5 class="mb-0 text-white">Results - {{ $alumniElection->title }}</h5><a href="{{ route('alumni-elections.show', $alumniElection) }}" class="btn btn-light btn-sm">Back</a></div>
    <div class="card-body">
        @foreach($positions as $position)
            <h5>{{ $position->name }}</h5>
            <table class="table table-bordered"><thead><tr><th>Candidate</th><th>Votes</th></tr></thead><tbody>
            @foreach($position->candidates as $candidate)
                <tr><td>{{ $candidate->surname }} {{ $candidate->first_name }} {{ $candidate->middle_name }}</td><td>{{ $candidate->votes_count }}</td></tr>
            @endforeach
            </tbody></table>
        @endforeach
    </div>
</div>
@endsection
