@extends('layouts.main')

@section('styles')
    @if(isset($styles))
        @foreach($styles as $style)
            <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
        @endforeach
    @endif

    <style>
        .content {
            padding-left: 40px;
        }

        .body-container {
            padding-left: 20px;
            padding-top: 20px;
        }
    </style>
@endsection

@section('breadcrumb')

@endsection

@section('content')

    <div class="content">

        <div class="header card">
            <div class="card-body">
                <h1 class="display-6 mb-3">
                    <i class="bi bi-person-lines-fill"></i> Add Student
                </h1>
            </div>
        </div>

        <div class="body-container">
            <div class="mb-4">

                @if(session('success'))
                    <div class="alert alert-success">
                        {{ session(key: 'success') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form class="row g-3" method="POST" action="{{ route('user.create_student') }}">


                    @csrf
                    <div class="row g-3">
                        <!-- Row 1: Basic Info -->
                        <div class="col-md-6">
                            <label for="inputFirstName" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="inputFirstName" name="first_name"
                                placeholder="First Name" required value="{{old('first_name')}}">
                        </div>
                        <div class="col-md-6">
                            <label for="inputLastName" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="inputLastName" name="last_name"
                                placeholder="Last Name" required value="{{old('last_name')}}">
                        </div>

                        <!-- Row 2: Credentials -->
                        <div class="col-md-6">
                            <label for="inputUserName" class="form-label">User Name</label>
                            <input type="text" class="form-control" id="inputUserName" name="user_name"
                                placeholder="User Name" required value="{{old('user_name')}}">
                        </div>
                        <div class="col-md-6">
                            <label for="inputEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="inputEmail" name="email" placeholder="Email"
                                required value="{{old('email')}}">
                        </div>

                        <!-- Row 3: Password -->
                        <div class="col-md-12">
                            <label for="inputPassword" class="form-label">Password</label>
                            <input type="password" class="form-control" id="inputPassword" name="password"
                                placeholder="Password" required>
                        </div>

                        <!-- Row 4: Academic Info -->
                        <div class="col-md-4">
                            <label for="inputYearLevel" class="form-label">Year Level</label>
                            <select class="form-control" id="inputYearLevel" name="year_level" required>
                                <option value="">Select Year Level</option>
                                <option value="1st Year" {{old('year_level') == '1st Year' ? 'selected' : ''}}>1st Year
                                </option>
                                <option value="2nd Year" {{old('year_level') == '2nd Year' ? 'selected' : ''}}>2nd Year
                                </option>
                                <option value="3rd Year" {{old('year_level') == '3rd Year' ? 'selected' : ''}}>3rd Year
                                </option>
                                <option value="4th Year" {{old('year_level') == '4th Year' ? 'selected' : ''}}>4th Year
                                </option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="inputStrand" class="form-label">Strand</label>
                            <select class="form-control" id="inputStrand" name="strand_id" required>
                                <option value="">Select Strand</option>
                                <option value="1" {{old('strand_id') == '1' ? 'selected' : ''}}>HUMMS</option>
                                <option value="2" {{old('strand_id') == '2' ? 'selected' : ''}}>ICT</option>
                                <option value="3" {{old('strand_id') == '3' ? 'selected' : ''}}>GAS</option>
                                <option value="4" {{old('strand_id') == '4' ? 'selected' : ''}}>ABM</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="inputSection" class="form-label">Section</label>
                            <select class="form-control" id="inputSection" name="section_id" required>
                                <option value="">Select Section</option>
                                <option value="1" {{old('section_id') == '1' ? 'selected' : ''}}>Gemini</option>
                                <option value="2" {{old('section_id') == '2' ? 'selected' : ''}}>Libra</option>
                                <option value="3" {{old('section_id') == '3' ? 'selected' : ''}}>Pisces</option>
                                <option value="4" {{old('section_id') == '4' ? 'selected' : ''}}>Aquarius</option>
                            </select>
                        </div>

                        <!-- Submit Button -->
                        <div class="col-12 text-center mt-4">
                            <button type="submit" class="btn btn-primary px-5 py-2">Add User</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>


@endsection

@section('scripts')
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection