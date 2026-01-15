@extends('layouts.main-student')

@section('styles')
    @if(isset($styles))
        @foreach($styles as $style)
            <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
        @endforeach
    @endif
    <style>
.grade-card {
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    border-width: 2px;
}
.grade-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

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
.grade-box.no-grade .grade-value {
    color: #adb5bd;
}

.filter-btn-group {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}
.filter-btn-group .btn {
    flex: 1;
    min-width: 120px;
}

@media (max-width: 768px) {
    .grade-value {
        font-size: 1.3rem;
    }
    .grade-label {
        font-size: 0.65rem;
    }
    .filter-btn-group .btn {
        flex: 1 1 calc(50% - 0.5rem);
        min-width: auto;
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
    <!-- Quarter Filter Card -->
    <div class="card card-outline card-secondary mb-3">
        <div class="card-body py-3">
            <label class="mb-2"><strong><i class="fas fa-calendar-alt"></i> Select Period:</strong></label>
            <div class="filter-btn-group">
                @foreach($quarters as $quarter)
                <button type="button" class="btn {{ $loop->first ? 'btn-primary' : 'btn-outline-primary' }} quarter-filter-btn {{ $loop->first ? 'active' : '' }}" data-filter="q{{ $quarter->order_number }}">
                    <i class="fas fa-calendar"></i> {{ $quarter->name }}
                </button>
                @endforeach
                <button type="button" class="btn btn-outline-primary quarter-filter-btn" data-filter="final">
                    <i class="fas fa-trophy"></i> Final Grade
                </button>
            </div>
        </div>
    </div>

    <!-- Grades Container -->
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
            gradeDetails: "{{ route('student.grades.details', ['classId' => ':classId', 'quarterId' => ':quarterId']) }}"
        };
    </script>
    
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection