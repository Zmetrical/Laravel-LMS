@extends('layouts.adminlte')

@section('title', 'Main')

@section('breadcrumb')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection



@section('content')
    <div class="input-group">
        <div class="custom-file">
            <input type="file" class="custom-file-input" id="exampleInputFile">
            <label class="custom-file-label" for="exampleInputFile">Choose file</label>
        </div>
        <div class="input-group-append">
            <span class="input-group-text">Upload</span>
        </div>
    </div>

    <div class="form-group">
        <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="customSwitch1">
            <label class="custom-control-label" for="customSwitch1">Toggle this custom switch element</label>
        </div>
    </div>

    <div class="form-group">
        <label>Custom Select</label>
        <select class="custom-select">
            <option>option 1</option>
            <option>option 2</option>
            <option>option 3</option>
            <option>option 4</option>
            <option>option 5</option>
        </select>
    </div>


    <div class="custom-control custom-checkbox">
        <input class="custom-control-input" type="checkbox" id="customCheckbox1" value="option1">
        <label for="customCheckbox1" class="custom-control-label">Custom Checkbox</label>
    </div>

    <div class="custom-control custom-checkbox">
        <input class="custom-control-input" type="checkbox" id="customCheckbox2" checked="">
        <label for="customCheckbox2" class="custom-control-label">Custom Checkbox checked</label>
    </div>

    <div class="custom-control custom-radio">
        <input class="custom-control-input" type="radio" id="customRadio1" name="customRadio">
        <label for="customRadio1" class="custom-control-label">Custom Radio</label>
    </div>

    <div class="custom-control custom-radio">
        <input class="custom-control-input" type="radio" id="customRadio2" name="customRadio" checked="">
        <label for="customRadio2" class="custom-control-label">Custom Radio checked</label>
    </div>

    <div class="card card-info">
        <div class="card-header">
            <h3 class="card-title">Horizontal Form</h3>
        </div>
        <!-- /.card-header -->
        <!-- form start -->
        <form class="form-horizontal">
            <div class="card-body">
                <div class="form-group row">
                    <label for="inputEmail3" class="col-sm-2 col-form-label">Email</label>
                    <div class="col-sm-10">
                        <input type="email" class="form-control" id="inputEmail3" placeholder="Email">
                    </div>
                </div>
                <div class="form-group row">
                    <label for="inputPassword3" class="col-sm-2 col-form-label">Password</label>
                    <div class="col-sm-10">
                        <input type="password" class="form-control" id="inputPassword3" placeholder="Password">
                    </div>
                </div>
                <div class="form-group row">
                    <div class="offset-sm-2 col-sm-10">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="exampleCheck2">
                            <label class="form-check-label" for="exampleCheck2">Remember me</label>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.card-body -->
            <div class="card-footer">
                <button type="submit" class="btn btn-info">Sign in</button>
                <button type="submit" class="btn btn-default float-right">Cancel</button>
            </div>
            <!-- /.card-footer -->
        </form>
    </div>

    <div>
    <a href="https://adminlte.io/themes/v3/pages/forms/advanced.html">
        Advance
    </a>
    </div>

@endsection




@section('scripts')
    <script>
    </script>
@endsection