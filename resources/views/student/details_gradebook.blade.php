@extends('layouts.main-student')

@section('styles')
    @if(isset($styles))
        @foreach($styles as $style)
            <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
        @endforeach
    @endif
    <style>
        .filter-btn-group {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .filter-btn-group .btn {
            flex: 1;
            min-width: 120px;
        }
        .quarter-card {
            border-left: 4px solid #007bff;
        }
        .quarter-card.locked {
            opacity: 0.95;
            background-color: #f8f9fa;
        }
        .card.border-dark {
            border-width: 1px !important;
        }
        @media (max-width: 768px) {
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
        <li class="breadcrumb-item"><a href="{{ route('student.list_grade') }}">My Grades</a></li>
        <li class="breadcrumb-item active">{{ $class->class_name }}</li>
    </ol>
@endsection

@section('content')
<br>
<div class="container-fluid">
    <!-- Class Header -->
    <div class="card card-outline card-primary mb-3">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h4 class="mb-1">
                        <i class="fas fa-book"></i> 
                        <strong>{{ $class->class_name }}</strong>
                    </h4>
                </div>
                <div class="col-md-4 text-right">
                    <a href="{{ route('student.list_grade') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card card-outline card-secondary mb-3">
        <div class="card-body py-3">
            <div class="row">
                <!-- Quarter Filter -->
                <div class="col-md-12 mb-2 mb-md-0">
                    <label class="mb-2"><strong><i class="fas fa-calendar-alt"></i> Select Period:</strong></label>
                    <div class="filter-btn-group">
                        @foreach($quarters as $quarter)
                        <button type="button" class="btn {{ $loop->first ? 'btn-primary' : 'btn-outline-primary' }} quarter-filter-btn {{ $loop->first ? 'active' : '' }}" data-filter="{{ $quarter->order_number }}">
                            <i class="fas fa-calendar"></i> {{ $quarter->name }}
                        </button>
                        @endforeach
                        <button type="button" class="btn btn-outline-primary quarter-filter-btn" data-filter="final">
                            <i class="fas fa-trophy"></i> Final Grade
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grades Content -->
    <div id="gradesContent">
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading grade details...</p>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script>
        const API_ROUTES = {
            getDetails: "{{ route('student.grades.details.data', ['classId' => $classId]) }}"
        };
        
        const CLASS_INFO = {
            ww_perc: {{ $class->ww_perc }},
            pt_perc: {{ $class->pt_perc }},
            qa_perc: {{ $class->qa_perce }}
        };
    </script>
    
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection