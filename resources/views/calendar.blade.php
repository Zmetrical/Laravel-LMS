@extends('layouts.main')

@section('breadcrumb')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('styles')
    <!-- calendar -->
    <link rel="stylesheet" href="{{ asset('plugins/fullcalendar/main.css') }}">
@endsection

@section('content')
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1>Calendar</h1>
        </div>
      </div>
    </div>

    <div class="container-fluid">
      <div class="row">
        <div class="col-md-3">
          <div class="sticky-top mb-3">
            <div class="card">
              <div class="card-header">
                <h4 class="card-title">Draggable Events</h4>
              </div>
              <div class="card-body">
                <div id="external-events">
                  <div class="external-event bg-primary">Lunch</div>
                  <div class="external-event bg-green">Go home</div>
                  <div class="external-event b  g-orange">Do homework</div>
                  <div class="external-event bg-red">Work on UI design</div>
                  <div class="external-event bg-light">Sleep tight</div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-md-9">
          <div class="card card-primary">
            <div class="card-body p-0">
              <div id="calendar"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

@endsection

@section('scripts')
    <!-- calendar -->
    <script src="{{ asset('plugins/moment/moment.min.js') }}"></script>
    <script src="{{ asset('plugins/fullcalendar/main.js') }}"></script>

    <script>
    $(function () {
    // Initialize the calendar
    var Calendar = FullCalendar.Calendar;
    var Draggable = FullCalendar.Draggable;

    var containerEl = document.getElementById('external-events');
    var checkbox = document.getElementById('drop-remove');
    var calendarEl = document.getElementById('calendar');

    // Initialize the external events
    new Draggable(containerEl, {
        itemSelector: '.external-event',
        eventData: function(eventEl) {
        return {
            title: eventEl.innerText,
            backgroundColor: window.getComputedStyle(eventEl, null).getPropertyValue('background-color'),
            borderColor: window.getComputedStyle(eventEl, null).getPropertyValue('background-color'),
            textColor: window.getComputedStyle(eventEl, null).getPropertyValue('color'),
        };
        }
    });

    var calendar = new Calendar(calendarEl, {
        headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        themeSystem: 'bootstrap',
        editable: true,
        droppable: true,
        
        // Sample events
        events: [
        {
            title: 'All Day Event',
            start: new Date(new Date().getFullYear(), new Date().getMonth(), 1),
            backgroundColor: '#f56954',
            borderColor: '#f56954',
            allDay: true
        },
        {
            title: 'Long Event',
            start: new Date(new Date().getFullYear(), new Date().getMonth(), 7),
            end: new Date(new Date().getFullYear(), new Date().getMonth(), 10),
            backgroundColor: '#f39c12',
            borderColor: '#f39c12'
        }
        ],

        drop: function(info) {
            info.draggedEl.parentNode.removeChild(info.draggedEl);
        }
    });

    calendar.render();
    });
    </script>
@endsection
