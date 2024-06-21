@extends('layout.empty')

@section('content')
    <h2 class="my-4">Registro</h2>
    <form action="{!! $me->url() !!}" method="POST">
        @component('component.card', ['name' => 'panel1'])
            @slot('slot')
                @include('component.input', ['type' => 'text', 'name' => 'name', 'label' => 'Nombre1'])
                @include('component.input', ['type' => 'email', 'name' => 'email', 'label' => 'Correo Electrónico1'])
            @endslot
        @endcomponent
        <div class="row">
            <div class="col-6">
                @component('component.card', ['name' => 'card1'])
                    @slot('slot')
                        @include('component.input', ['type' => 'text', 'name' => 'name', 'label' => 'Nombre2'])
                        @include('component.input', ['type' => 'email', 'name' => 'email', 'label' => 'Correo Electrónico2'])
                    @endslot
                @endcomponent
            </div>
            <div class="col-6">
                @component('component.card', ['name' => 'card1'])
                    @slot('slot')
                        @include('component.input', ['type' => 'text', 'name' => 'name', 'label' => 'Nombre3'])
                        @include('component.input', ['type' => 'email', 'name' => 'email', 'label' => 'Correo Electrónico3'])
                    @endslot
                @endcomponent
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
@endpush
