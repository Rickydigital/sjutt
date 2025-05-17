@extends('layouts.admin')

@section('content')
    <div class="content">
        <div class="animated fadeIn">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Edit Profile</h4>
                        </div>
                        <div class="card-body">
                            <!-- Success/Error Messages -->
                            @if (session('status') === 'profile-updated' || session('status') === 'password-updated')
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <strong>Success!</strong> {{ session('status') === 'profile-updated' ? 'Profile updated successfully.' : 'Password updated successfully.' }}
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">×</span>
                                    </button>
                                </div>
                            @endif

                            @if ($errors->any())
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <strong>Error!</strong> Please check the form for errors.
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">×</span>
                                    </button>
                                </div>
                            @endif

                            <!-- Profile Information Section -->
                            <section class="mb-4">
                                <h5 class="text-primary">{{ __('Profile Information') }}</h5>
                                <p class="text-muted small">{{ __('Update your account\'s profile information and email address.') }}</p>

                                <form method="POST" action="{{ route('profile.update') }}" class="form-horizontal mt-4">
                                    @csrf
                                    @method('patch')

                                    <!-- Name -->
                                    <div class="form-group row">
                                        <label for="name" class="col-md-3 col-form-label">{{ __('Name') }}</label>
                                        <div class="col-md-9">
                                            <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- Email -->
                                    <div class="form-group row">
                                        <label for="email" class="col-md-3 col-form-label">{{ __('Email') }}</label>
                                        <div class="col-md-9">
                                            <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required autocomplete="username">
                                            @error('email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail())
                                                <small class="form-text text-muted">
                                                    {{ __('Your email address is unverified.') }}
                                                    <button type="submit" form="send-verification" class="btn btn-link p-0 m-0 align-baseline">{{ __('Click here to re-send the verification email.') }}</button>
                                                </small>
                                                @if (session('status') === 'verification-link-sent')
                                                    <small class="form-text text-success">{{ __('A new verification link has been sent to your email address.') }}</small>
                                                @endif
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Phone -->
                                    <div class="form-group row">
                                        <label for="phone" class="col-md-3 col-form-label">{{ __('Phone') }}</label>
                                        <div class="col-md-9">
                                            <input id="phone" name="phone" type="text" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $user->phone) }}" autocomplete="tel">
                                            @error('phone')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- Gender -->
                                    <div class="form-group row">
                                        <label for="gender" class="col-md-3 col-form-label">{{ __('Gender') }}</label>
                                        <div class="col-md-9">
                                            <select id="gender" name="gender" class="form-control @error('gender') is-invalid @enderror">
                                                <option value="{{ old('phone', $user->phone) }}">{{ $user->gender }}</option>
                                                <option value="Male" {{ $user->gender === 'Male' ? 'selected' : '' }}>Male</option>
                                                <option value="Female" {{ $user->gender === 'Female' ? 'selected' : '' }}>Female</option>
                                                {{-- <option value="Other" {{ $user->gender === 'Other' ? 'selected' : '' }}>Other</option> --}}
                                            </select>
                                            @error('gender')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <div class="col-md-9 offset-md-3">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fa fa-save"></i> {{ __('Save Profile') }}
                                            </button>
                                        </div>
                                    </div>
                                </form>

                                <!-- Email Verification Form -->
                                <form id="send-verification" method="POST" action="{{ route('verification.send') }}" class="d-none">
                                    @csrf
                                </form>
                            </section>

                            <!-- Password Update Section -->
                            <section>
                                <h5 class="text-primary">{{ __('Update Password') }}</h5>
                                <p class="text-muted small">{{ __('Ensure your account is using a long, random password to stay secure.') }}</p>

                                <form method="POST" action="{{ route('password.update') }}" class="form-horizontal mt-4">
                                    @csrf
                                    @method('put')

                                    <!-- Current Password -->
                                    <div class="form-group row">
                                        <label for="current_password" class="col-md-3 col-form-label">{{ __('Current Password') }}</label>
                                        <div class="col-md-9">
                                            <input id="current_password" name="current_password" type="password" class="form-control @error('current_password') is-invalid @enderror" autocomplete="current-password">
                                            @error('current_password')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- New Password -->
                                    <div class="form-group row">
                                        <label for="password" class="col-md-3 col-form-label">{{ __('New Password') }}</label>
                                        <div class="col-md-9">
                                            <input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password">
                                            @error('password')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- Confirm Password -->
                                    <div class="form-group row">
                                        <label for="password_confirmation" class="col-md-3 col-form-label">{{ __('Confirm Password') }}</label>
                                        <div class="col-md-9">
                                            <input id="password_confirmation" name="password_confirmation" type="password" class="form-control @error('password_confirmation') is-invalid @enderror" autocomplete="new-password">
                                            @error('password_confirmation')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <div class="col-md-9 offset-md-3">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fa fa-save"></i> {{ __('Save Password') }}
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </section>
                        </div><!-- /.card-body -->
                    </div><!-- /.card -->
                </div><!-- /.col-lg-8 -->
            </div><!-- /.row -->
        </div><!-- /.animated -->
    </div><!-- /.content -->
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);
        });
    </script>
@endsection