@extends('layouts.adminlte')

@section('title', 'Main')

@section('breadcrumb')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection



@section('content')
<a href="https://adminlte.io/themes/v3/pages/tables/simple.html">SimpleTable</a>
<a href="https://adminlte.io/themes/v3/pages/tables/data.html">DataTable</a>
<a href="https://adminlte.io/themes/v3/pages/tables/jsgrid.html">JSGrid</a>
@endsection




@section('scripts')
    <script>
        // Your custom JavaScript here
        console.log('Dashboard loaded');
    </script>
@endsection
