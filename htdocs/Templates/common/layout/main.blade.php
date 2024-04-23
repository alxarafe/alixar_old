<!DOCTYPE html>
<html lang="{!! $me->lang !!}">
<head>
    @include('partial.head')
</head>
<body class="{!! $me->body_class !!}">
@include('partial.body')

{{-- Common scripts --}}

@stack('scripts')
</body>
</html>
