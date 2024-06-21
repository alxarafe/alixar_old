<!DOCTYPE html>
<html lang="{!! $me->lang !!}">
<head>
    @include('partial.head')
</head>
<body class="container">
@include('partial.body')
@stack('scripts')
</body>
</html>
