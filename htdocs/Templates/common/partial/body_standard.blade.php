<div id="id_container" class="id_container">
    @include('partial.top_bar')
    @include('partial.side_bar')
    <div id="id-right">
        @include('alerts')
        @yield('content')
    </div>
</div>
