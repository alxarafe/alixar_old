<!DOCTYPE html>
<html lang="{!! $me->lang !!}">
<head>
    @include('partial.head')
</head>
<body class="{!! $me->body_class !!}">
@yield('content')
@stack('scripts')
</body>
</html>
