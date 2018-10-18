@extends('layouts.master')
<style type="text/css">
	.fc-event{
	  height: 80px !important;
	}
	/*fc-day fc-widget-content fc-mon fc-past*/
	.fc-past
	{
		background-color: #0984e3;
		border-color: #0984e3;
	}
	.fc-rigid
	{
		background-color: #0984e3;
		border-color: #0984e3;
	}
	.fc-day
	{
		border-color: #0984e3;
	}
	.fc-state-highlight{
		background-color: #0984e3;
	}
	.fc-sun{
		background-color: #f1f1f1 !important;
	}
	.fc-sat{
		background-color: #f1f1f1 !important;
	}
</style>
@section('content')

    {!! $calendar->calendar() !!}
    {!! $calendar->script() !!}

@stop
<footer>
    @include('layouts.footer')
</footer>