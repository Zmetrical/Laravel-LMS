@extends('layouts.main')

@if(isset($styles))
    @foreach($styles as $style)
        <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
    @endforeach
@endif

@section('breadcrumb')

@endsection

@section('content')
<h1>Dashboard</h1>
@endsection


@if(isset($scripts))
    @foreach($scripts as $script)
        <script src="{{ asset('js/' . $script) }}"></script>
    @endforeach
@endif