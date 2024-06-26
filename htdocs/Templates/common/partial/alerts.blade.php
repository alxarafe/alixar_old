{{--
    TODO: Pending analysis of how you will receive information about messages, warnings and errors
--}}

@foreach($me->alerts as $alert)
    <div class="alert alert-{!! $alert['type'] !!}" role="alert">
        {!! $alert['text'] !!}
    </div>
@endforeach
