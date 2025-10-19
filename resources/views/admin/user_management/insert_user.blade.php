@extends('layouts.main')

@section('styles')
    @if(isset($styles))
        @foreach($styles as $style)
            <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
        @endforeach
    @endif

    <link rel="stylesheet" href="{{ asset('plugins/bs-stepper/css/bs-stepper.min.css') }}">
@endsection

@section('breadcrumb')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>
                <i class="fas fa-user-graduate"></i> Student Information
            </h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item active">Student Registration</li>
            </ol>
        </div>
    </div>
@endsection

@section('content')
    <div class="bs-stepper">
        <div class="bs-stepper-header" role="tablist">
            <div class="step" data-target="#step-1">
                <button type="button" class="step-trigger" role="tab" aria-controls="step-1" id="stepper-trigger-1">
                    <span class="bs-stepper-circle">1</span>
                    <span class="bs-stepper-label">Academic Info</span>
                </button>
            </div>
            <div class="line"></div>
            <div class="step" data-target="#step-2">
                <button type="button" class="step-trigger" role="tab" aria-controls="step-2" id="stepper-trigger-2">
                    <span class="bs-stepper-circle">2</span>
                    <span class="bs-stepper-label">Personal Info</span>
                </button>
            </div>
        </div>

        <div class="bs-stepper-content">
            <form action="{{ route('insert.student') }}" method="POST">
                @csrf

                <!-- Step 1: Academic Info -->
                <div id="step-1" class="content" role="tabpanel" aria-labelledby="stepper-trigger-1">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Academic Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="strand" class="font-weight-bold">
                                        Strand <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-control form-control-lg" id="strand" name="strand" required>
                                        <option hidden disabled selected>Select Strand</option>
                                        @foreach($strands as $strand)
                                            <option value="{{ $strand->id }}">{{ $strand->code }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="level" class="font-weight-bold">
                                        Year Level <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-control form-control-lg" id="level" name="level" required>
                                        <option hidden disabled selected>Select Year Level</option>
                                        @foreach($levels as $level)
                                            <option value="{{ $level->id }}">{{ $level->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="section" class="font-weight-bold">
                                        Section <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-control form-control-lg" id="section" name="section" required>
                                        <option hidden disabled selected>Select Section</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="button" class="btn btn-primary float-right" onclick="stepper.next()">
                                Next <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Personal Info -->
                <div id="step-2" class="content" role="tabpanel" aria-labelledby="stepper-trigger-2">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Personal Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="email">Email <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="email" name="email" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="firstName">First Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="firstName" name="first_name" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="middlename">Middle Initial</label>
                                        <input type="text" class="form-control" id="middlename" name="middle_name">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="lastName">Last Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="lastName" name="last_name" required>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="button" class="btn btn-secondary" onclick="stepper.previous()">
                                <i class="fas fa-arrow-left"></i> Previous
                            </button>
                            <button type="submit" class="btn btn-success float-right">
                                <i class="fas fa-save"></i> Submit
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('plugins/bs-stepper/js/bs-stepper.min.js') }}"></script>

    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif

    <script>
        let stepper;
        $(document).ready(function () {
            stepper = new Stepper($('.bs-stepper')[0]);
        });
    </script>
@endsection