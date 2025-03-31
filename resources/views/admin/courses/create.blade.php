@extends('layouts.admin')

@section('content')
    <div class="content">
        <div class="animated fadeIn">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="card">
                        <div class="card-header" style="background-color: #4B2E83; color: white;">
                            <h4 class="card-title"><i class="fa fa-plus"></i> Create New Course</h4>
                        </div>
                        <div class="card-body">
                            <!-- Success/Error Messages -->
                            @if (session('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <strong>Success!</strong> {{ session('success') }}
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">×</span>
                                    </button>
                                </div>
                            @endif
                            @if (session('error'))
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <strong>Error!</strong> {{ session('error') }}
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

                            <!-- Form -->
                            <form action="{{ route('courses.store') }}" method="POST" class="form-horizontal">
                                @csrf
                                <div class="form-group row">
                                    <label for="school_faculty" class="col-md-3 col-form-label"><i class="fa fa-university"></i> School/Faculty</label>
                                    <div class="col-md-9">
                                        <input type="text" name="school_faculty" id="school_faculty" class="form-control @error('school_faculty') is-invalid @enderror" value="{{ old('school_faculty') }}" required>
                                        @error('school_faculty')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="academic_programme" class="col-md-3 col-form-label"><i class="fa fa-graduation-cap"></i> Program Name</label>
                                    <div class="col-md-9">
                                        <input type="text" name="academic_programme" id="academic_programme" class="form-control @error('academic_programme') is-invalid @enderror" value="{{ old('academic_programme') }}" required>
                                        @error('academic_programme')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="entry_qualifications" class="col-md-3 col-form-label"><i class="fa fa-certificate"></i> Entry Qualifications</label>
                                    <div class="col-md-9">
                                        <textarea name="entry_qualifications" id="entry_qualifications" class="form-control @error('entry_qualifications') is-invalid @enderror" rows="4" required>{{ old('entry_qualifications') }}</textarea>
                                        @error('entry_qualifications')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="tuition_fee_per_year" class="col-md-3 col-form-label"><i class="fa fa-money"></i> Tuition Fee</label>
                                    <div class="col-md-9">
                                        <input type="number" name="tuition_fee_per_year" id="tuition_fee_per_year" class="form-control @error('tuition_fee_per_year') is-invalid @enderror" value="{{ old('tuition_fee_per_year') }}" step="0.01" required>
                                        @error('tuition_fee_per_year')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="duration" class="col-md-3 col-form-label"><i class="fa fa-clock-o"></i> Duration</label>
                                    <div class="col-md-9">
                                        <input type="text" name="duration" id="duration" class="form-control @error('duration') is-invalid @enderror" value="{{ old('duration') }}" required>
                                        @error('duration')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <div class="col-md-9 offset-md-3">
                                        <button type="submit" class="btn" style="background-color: #4B2E83; color: white;">
                                            <i class="fa fa-save"></i> Create Course
                                        </button>
                                    </div>
                                </div>
                            </form>
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
            // Fade out alerts after 5 seconds
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);
        });
    </script>
@endsection