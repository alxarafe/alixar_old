<!DOCTYPE html>
<html lang="{!! $me->lang !!}">
<head>
    @include('partial.head')
</head>
<body class="container">
@yield('content')
@stack('scripts')
</body>
</html>
