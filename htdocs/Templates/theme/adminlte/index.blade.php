@extends('theme.adminlte.layout.main')

@section('content')
    @if ($self->alert)
        <div class="alert alert-danger" role="alert">
            {!! $self->alert !!}
        </div>
    @endif
    @if ($self->message)
        <div class="alert alert-success" role="alert">
            {!! $self->message !!}
        </div>
    @endif
    <div class="index-box">
    </div>
    <!-- /.index-box -->
@endsection
