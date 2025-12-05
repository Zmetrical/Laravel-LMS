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
            opacity: 0.9;
            background-color: #f8f9fa;
        }
        .component-card {
            transition: all 0.2s;
        }
        .component-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
                    <p class="text-muted mb-0">
                        <small>{{ $class->class_code }}</small>
                    </p>
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
                <div class="col-md-6 mb-2 mb-md-0">
                    <label class="mb-2"><strong>Select Period:</strong></label>
                    <div class="filter-btn-group">
                        <button type="button" class="btn btn-outline-primary quarter-filter-btn active" data-filter="all">
                            <i class="fas fa-th"></i> All
                        </button>
                        <button type="button" class="btn btn-outline-primary quarter-filter-btn" data-filter="1">
                            <i class="fas fa-calendar"></i> Q1
                        </button>
                        <button type="button" class="btn btn-outline-primary quarter-filter-btn" data-filter="2">
                            <i class="fas fa-calendar"></i> Q2
                        </button>
                        <button type="button" class="btn btn-outline-primary quarter-filter-btn" data-filter="final">
                            <i class="fas fa-trophy"></i> Final
                        </button>
                    </div>
                </div>

                <!-- Component Filter -->
                <div class="col-md-6">
                    <label class="mb-2"><strong>Filter Component:</strong></label>
                    <div class="filter-btn-group">
                        <button type="button" class="btn btn-outline-secondary component-filter-btn active" data-filter="all">
                            <i class="fas fa-list"></i> All
                        </button>
                        <button type="button" class="btn btn-outline-secondary component-filter-btn" data-filter="WW">
                            WW ({{ $class->ww_perc }}%)
                        </button>
                        <button type="button" class="btn btn-outline-secondary component-filter-btn" data-filter="PT">
                            PT ({{ $class->pt_perc }}%)
                        </button>
                        <button type="button" class="btn btn-outline-secondary component-filter-btn" data-filter="QA">
                            QA ({{ $class->qa_perce }}%)
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