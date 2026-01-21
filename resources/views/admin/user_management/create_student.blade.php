@extends('layouts.main')

@section('styles')
    @if(isset($styles))
        @foreach($styles as $style)
            <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
        @endforeach
    @endif
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
    <style>
        .student-card {
            transition: all 0.3s ease;
        }
        .student-card:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .btn-icon {
            width: 40px;
            height: 40px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
    </style>
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
        <li class="breadcrumb-item active">Student Registration</li>
    </ol>
@endsection

@section('content')

<br>
<div class="container-fluid">
    <form id="insert_students" method="POST">
        @csrf
        
        <!-- Academic Information Section -->
        <div class="card card-primary card-outline">
            <div class="card-header">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group mb-0">
                            <label>Strand <span class="text-danger">*</span></label>
                            <select class="form-control" id="strand" name="strand_id" required>
                                <option value="" selected disabled>Select Strand</option>
                                @foreach($strands as $strand)
                                    <option value="{{ $strand->id }}">{{ $strand->code }} - {{ $strand->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group mb-0">
                            <label>Year Level <span class="text-danger">*</span></label>
                            <select class="form-control" id="level" name="level_id" required>
                                <option value="" selected disabled>Select Year Level</option>
                                @foreach($levels as $level)
                                    <option value="{{ $level->id }}">{{ $level->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group mb-0">
                            <label>Actions</label>
                            <div class="d-flex">
                                <button type="button" class="btn btn-secondary mr-2" id="importExcel">
                                    <i class="fas fa-upload mr-1"></i> Import Excel
                                </button>
                                <button type="button" class="btn btn-secondary" id="generateTemplate">
                                    <i class="fas fa-download mr-1"></i> Template
                                </button>
                            </div>
                            <input type="file" class="d-none" id="excelFile" accept=".xlsx,.xls">
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">

                <!-- Section Capacity Display -->
                <div class="row mt-3">
                    <div class="col-12">
                        <div id="sectionCapacityContainer">
                            <div class="alert alert-primary mb-0">
                                <i class="fas fa-info-circle"></i> Select Strand and Level to view available sections
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            
        </div>

        <!-- Student Entry Section -->
        <div class="card card-primary card-outline">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0"><i class="fas fa-users mr-2"></i>Student Form</h3>
                    <div class="d-flex align-items-center">
                        <!-- Add Students Control -->
                        <div class="input-group mr-2" style="width: 200px;">
                            <input type="number" class="form-control" id="numRows" value="1" min="1" max="100" placeholder="Students">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="button" id="addStudentsBtn" title="Add students">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <!-- Total Students Badge -->
                        <span class="badge badge-primary badge-counter" id="studentCount" style="font-size: 0.95rem; padding: 0.5rem 1rem;">0 Students</span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Students Container -->
                <div id="studentsContainer">
                    <!-- Student cards will be generated here -->
                </div>

                <div class="text-center py-4" id="emptyState">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No students added yet. Enter the number of students and click the plus button to begin.</p>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary btn-lg float-right" id="submitBtn" disabled>
                    <i class="fas fa-save mr-2"></i> Save All Students
                </button>
            </div>
        </div>

        <input type="hidden" id="selectedStrand" name="strand_id">
        <input type="hidden" id="selectedLevel" name="level_id">
    </form>
</div>
@endsection

@section('scripts')
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.3.0/exceljs.min.js"></script>

<script>
const API_ROUTES = {
    getSections: "{{ route('sections.data') }}",
    insertStudents: "{{ route('admin.insert_students') }}",
    redirectAfterSubmit: "{{ route('admin.list_student') }}"
};
</script>

@if(isset($scripts))
    @foreach($scripts as $script)
        <script src="{{ asset('js/' . $script) }}"></script>
    @endforeach
@endif
@endsection