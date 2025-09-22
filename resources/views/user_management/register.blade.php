@extends('layouts.main')

@section('styles')
    @if(isset($styles))
        @foreach($styles as $style)
            <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
        @endforeach
    @endif

    <style>
        .content{
            padding-left: 40px;
        }

        .body-container{
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

        <form class="row g-3" method="POST" action="{{ route('userlist.create') }}">


            @csrf
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="inputFirstName" class="form-label">First Name </label>
                    <input type="text" class="form-control" id="inputFirstName" name="first_name" placeholder="First Name" required value="{{old('first_name')}}">
                </div>
                <div class="col-md-3">
                    <label for="inputLastName" class="form-label">Last Name</label>
                    <input type="text" class="form-control" id="inputLastName" name="last_name" placeholder="Last Name" required value="{{old('last_name')}}">
                </div>
                <div class="col-md-6">
                    <label for="inputEmail4" class="form-label">Email</label>
                    <input type="email" class="form-control" id="inputEmail4" name="email" required value="{{old('email')}}">
                </div>
                <div class="col-md-6">
                    <label for="inputPassword4" class="form-label">Password</label>
                    <input type="password" class="form-control" id="inputPassword4" name="password" required>
                </div>


                <div class="col-md-3">
                    <label for="inputYearLevel" class="form-label">Year Level</label>
                    <select class="form-control" id="inputYearLevel" name="year_level" required>
                        <option value="1st Year">1st Year</option>
                        <option value="2nd Year">2nd Year</option>
                        <option value="3rd Year">3rd Year</option>
                        <option value="4th Year">4th Year</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="inputYearLevel" class="form-label">Year Level</label>
                    <select class="form-control" id="inputYearLevel" name="year_level" required>
                        <option value="1">HUMMS</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                    </select>
                </div>


                <div class="col-md-3">
                    <label for="inputSection" class="form-label">Section</label>
                    <input type="text" class="form-control" id="inputSection" name="section" placeholder="A" required value="{{old('section')}}">
                </div>


                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Add User</button>
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
