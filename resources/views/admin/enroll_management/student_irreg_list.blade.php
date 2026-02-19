@extends('layouts.main')

@section('styles')
    <link rel="stylesheet" href="{{ asset('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
        <li class="breadcrumb-item active">Irregular Student List</li>
    </ol>
@endsection

@section('content')
<br>
        <div class="container-fluid">
            <!-- School Year Info -->
            <div class="alert alert-dark alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <h5><i class="icon fas fa-calendar-alt"></i> {{ $activeSemesterDisplay ?? 'No Active Semester' }}</h5>
            </div>

            <!-- Students Table Card -->
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-users"></i> Irregular Students List</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="irregularStudentsTable" class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Student Number</th>
                                    <th>Full Name</th>
                                    <th>Grade Level</th>
                                    <th>Strand</th>
                                    <th>Section</th>
                                    <th>Enrolled Classes</th>
                                    <th width="120">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
@endsection

@section('scripts')
    <script src="{{ asset('plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    
    <script>
        const API_ROUTES = {
            getStudents: "{{ route('admin.students.list') }}",
            enrollmentPage: "{{ route('admin.student_class_enrollment', ['id' => ':id']) }}"
        };
    </script>
    
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection