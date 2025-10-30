@extends('layouts.main-student')

@section('styles')
    @if(isset($styles))
        @foreach($styles as $style)
            <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
        @endforeach
    @endif
@endsection

@section('breadcrumb')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>
                <i class="fas fa-user-graduate"></i> Classess
            </h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route(name: 'student.home') }}">Home</a></li>
            </ol>
        </div>
    </div>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row" id="classCardsContainer">
            <!-- Class cards will be populated here by JavaScript -->
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