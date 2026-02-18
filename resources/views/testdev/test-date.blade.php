@extends('layouts.main-test')
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
@section('styles')
    @if(isset($styles))
        @foreach($styles as $style)
            <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
        @endforeach
    @endif
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
@endsection

@section('breadcrumb')
<nav aria-label="breadcrumb" class="breadcrumb-custom">
    <i class="fas fa-flask breadcrumb-icon"></i>
    <ol class="breadcrumb mb-0 bg-transparent">
        <li class="breadcrumb-item active">Quiz Date Testing</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10 col-lg-8">

        <!-- Mock Server Time Card -->
        <div class="card card-secondary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-clock mr-2"></i>Mock Server Time</h3>
            </div>
            <div class="card-body">
                <div class="form-group mb-3">
                    <label>Set Fake Date & Time</label>
                    <input type="datetime-local" class="form-control" id="mockDatetime" value="2026-02-19T13:30">
                </div>
                <button class="btn btn-secondary" id="setMockTime">
                    <i class="fas fa-clock mr-1"></i> Set Mock Time
                </button>
                <button class="btn btn-default ml-2" id="clearMockTime">
                    <i class="fas fa-times mr-1"></i> Clear (Use Real Time)
                </button>
            </div>
        </div>

        <!-- Feature Flags Card -->
        <div class="card card-secondary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-toggle-on mr-2"></i>Feature Flags</h3>
                <div class="card-tools">
                    <span class="badge badge-secondary" id="serverTimeClock">—</span>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th width="220">Flag</th>
                            <th>Description</th>
                            <th width="80" class="text-center">Toggle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="align-middle"><code>bypass_date_check</code></td>
                            <td class="align-middle">
                                When <strong>OFF</strong>, availability uses server time (Manila UTC+8) — student's system clock is ignored.
                                <br>
                                When <strong>ON</strong>, all quizzes are accessible regardless of date.
                                Resets automatically after 24 hours.
                            </td>
                            <td class="align-middle text-center">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox"
                                           class="custom-control-input dev-flag-toggle"
                                           id="flag_bypass_date_check"
                                           data-flag="bypass_date_check">
                                    <label class="custom-control-label" for="flag_bypass_date_check"></label>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>

<script>
    const API_ROUTES = {
        devFlagsToggle: "{{ route('testdev.toggle.date') }}",
        mockTime:       "{{ route('testdev.mock.time') }}"
    };

    $(document).ready(function () {
        updateServerClock();
        setInterval(updateServerClock, 60000);

        // Feature flag toggle
        $(document).on('change', '.dev-flag-toggle', function () {
            const $el  = $(this);
            const flag = $el.data('flag');
            const val  = $el.is(':checked') ? 1 : 0;

            $el.prop('disabled', true);

            $.ajax({
                url:  API_ROUTES.devFlagsToggle,
                type: 'POST',
                data: {
                    flag:   flag,
                    value:  val,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function () {
                    Swal.fire({
                        icon: 'success',
                        title: (val ? 'Enabled' : 'Disabled'),
                        text: flag,
                        timer: 1500,
                        showConfirmButton: false
                    });
                },
                error: function () {
                    $el.prop('checked', !val);
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to update flag. Try again.' });
                },
                complete: function () {
                    $el.prop('disabled', false);
                }
            });
        });

        // Mock time - set
        $('#setMockTime').on('click', function () {
            const val = $('#mockDatetime').val();
            if (!val) {
                Swal.fire({ icon: 'warning', title: 'No date selected', text: 'Pick a date and time first.' });
                return;
            }

            $.post(API_ROUTES.mockTime, {
                datetime: val,
                _token:   $('meta[name="csrf-token"]').attr('content')
            })
            .done(function () {
                Swal.fire({
                    icon: 'success',
                    title: 'Mock Time Set',
                    text: val,
                    timer: 1500,
                    showConfirmButton: false
                });
            })
            .fail(function () {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to set mock time.' });
            });
        });

        // Mock time - clear
        $('#clearMockTime').on('click', function () {
            $.post(API_ROUTES.mockTime, {
                datetime: '',
                _token:   $('meta[name="csrf-token"]').attr('content')
            })
            .done(function () {
                $('#mockDatetime').val('');
                Swal.fire({
                    icon: 'success',
                    title: 'Cleared',
                    text: 'Now using real server time.',
                    timer: 1500,
                    showConfirmButton: false
                });
            })
            .fail(function () {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to clear mock time.' });
            });
        });
    });

    function updateServerClock() {
        $('#serverTimeClock').text(
            new Date().toLocaleString('en-PH', {
                timeZone:  'Asia/Manila',
                dateStyle: 'medium',
                timeStyle: 'short'
            }) + ' (Manila)'
        );
    }
</script>

@if(isset($scripts))
    @foreach($scripts as $script)
        <script src="{{ asset('js/' . $script) }}"></script>
    @endforeach
@endif
@endsection