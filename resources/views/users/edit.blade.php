@extends('components.app-main-layout')

@section('content')
    <div class="card p-5">
        <h1>Edit User</h1>
        <form method="POST" action="{{ route('users.update', $user) }}">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="form-group mb-3 col-md-6">
                    <label for="name">Name</label>
                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name', $user->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group mb-3 col-md-6">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email"
                        class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}"
                        required>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                @if (Auth::user()->hasRole('Admin'))
                <div class="form-group mb-3 col-md-6">
                    <label for="role">Role</label>
                    <select name="role" id="role" class="form-control @error('role') is-invalid @enderror" required>
                        <option value="">Select a role</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" {{ $currentRole == $role->id ? 'selected' : '' }}>
                                {{ $role->name }}</option>
                        @endforeach
                    </select>
                    @error('role')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                @endif
                <div class="form-group mb-3 col-md-6">
                    <label for="password">New Password (leave blank to keep current)</label>
                    <input type="password" name="password" id="password"
                        class="form-control @error('password') is-invalid @enderror">
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group mb-3 col-md-6">
                    <label for="phone">Phone</label>
                    <input type="text" name="phone" id="phone"
                        class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $user->phone) }}">
                    @error('phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group mb-3 col-md-6">
                    <label for="gender">Gender</label>
                    <select name="gender" id="gender" class="form-control @error('gender') is-invalid @enderror">
                        <option value="">Select Gender</option>
                        <option value="Male" {{ old('gender', $user->gender) == 'Male' ? 'selected' : '' }}>Male</option>
                        <option value="Female" {{ old('gender', $user->gender) == 'Female' ? 'selected' : '' }}>Female
                        </option>
                        {{-- <option value="Other" {{ old('gender', $user->gender) == 'Other' ? 'selected' : '' }}>Other --}}
                        </option>
                    </select>
                    @error('gender')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class=" d-flex flex-row justify-content-end ">
                <a href="{{ route('users.index') }}" class="btn btn-outline-danger  mx-2">Cancel</a>
                <button type="submit" class="btn btn-primary">Update User</button>
            </div>
        </form>
    </div>
@endsection
