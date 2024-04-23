<body class="sidebar-expand-lg bg-body-tertiary">
<!--begin::App Wrapper-->
<div class="app-wrapper">
    @include('partial.body_header')
    @include('partial.body_sidebar')
    <!--begin::App Main-->
    <main class="app-main"> <!--begin::App Content Header-->
        @yield('content')
    </main>
    <!--end::App Main-->

    @include('partial.body_footer')

</div>
<!--end::App Wrapper-->

@include('partial.body_scripts')
