@extends('layouts.main')

@section('styles')
    @if(isset($styles))
        @foreach($styles as $style)
            <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
        @endforeach
    @endif

    <style>

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