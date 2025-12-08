@extends('layouts.main-student')

@section('styles')
    @if(isset($styles))
        @foreach($styles as $style)
            <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
        @endforeach
    @endif
    <style>
/* Simplified Card Styling */
.grade-card {
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    border-width: 2px;
}
.grade-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* Unified Grade Box Styling */
.grade-box {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 10px 6px;
    text-align: center;
    border: 2px solid #e9ecef;
    transition: all 0.2s ease;
}

.grade-box.no-grade {
    border-style: dashed;
}

.grade-label {
    font-size: 0.7rem;
    font-weight: 600;
    color: #6c757d;
    text-transform: uppercase;
    margin-bottom: 4px;
}

.grade-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #495057;
    line-height: 1;
}
.grade-box.has-grade .grade-value {
    color: #007bff;
}
.grade-box.final-box.has-grade .grade-value {
    color: #28a745;
}
.grade-box.no-grade .grade-value {
    color: #adb5bd;
}

/* Compact row spacing */
.row.g-2 {
    margin-left: -4px;
    margin-right: -4px;
}
.row.g-2 > [class*='col-'] {
    padding-left: 4px;
    padding-right: 4px;
}

/* Badge styling */
.badge-light {
    background: #e9ecef;
    color: #495057;
    font-weight: 600;
    padding: 4px 8px;
    font-size: 0.75rem;
}

.gap-3 {
    gap: 0.75rem;
}

@media (max-width: 768px) {
    .grade-value {
        font-size: 1.3rem;
    }
    .grade-label {
        font-size: 0.65rem;
    }
}
    </style>
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('student.home') }}">Home</a></li>
        <li class="breadcrumb-item active">My Grades</li>
    </ol>
@endsection

@section('content')
<div class="container-fluid">
    <div id="gradesContainer">
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading your grades...</p>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script>
        const API_ROUTES = {
            getGrades: "{{ route('student.grades.list') }}",
            gradeDetails: "{{ route('student.grades.details', ['classId' => ':classId']) }}"
        };
    </script>
    
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection