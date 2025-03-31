@extends('layouts.admin')

@section('content')
    <div class="container">
        <h1>User Details</h1>
        <p><strong>Name:</strong> {{ $user->name }}</p>
        <p><strong>Email:</strong> {{ $user->email }}</p>
        <p><strong>Role:</strong> {{ $user->roles->first()->name ?? 'No Role' }}</p>
        <p><strong>Status:</strong> {{ $user->status }}</p>
        <p><strong>Phone:</strong> {{ $user->phone ?? 'N/A' }}</p>
        <p><strong>Gender:</strong> {{ $user->gender ?? 'N/A' }}</p>
        <a href="{{ route('users.index') }}" class="btn btn-secondary">Back to Users</a>
    </div>
@endsection