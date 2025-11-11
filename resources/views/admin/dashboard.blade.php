@extends('layouts.main')

@section('styles')
    @if(isset($styles))
        @foreach($styles as $style)
            <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
        @endforeach
    @endif
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item active">Home</li>
    </ol>
@endsection

@section('content')

@endsection

@section('scripts')

    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif

    <script>
        let stepper;
        $(document).ready(function () {
            stepper = new Stepper($('.bs-stepper')[0]);
        });
    </script>
@endsection