@extends('master/install_layout')

@section('body')
    <tbody>
    <input type="hidden" name="testpost" value="ok">
    <input type="hidden" name="action" value="step4">
    <tr>
        <div>
            <h3>
                <img class="valignmiddle inline-block paddingright" src="Resources/img/opticons/build/svg/database.svg" width="20" alt="Database">{!! $lang->trans("Database") !!}
            </h3>
            @if($fatalError)
                <td>{!! $fatalError !!}</td>
            @else
            <table cellspacing="0" style="padding: 4px 4px 4px 0" border="0" width="100%">
                <tr>
                    <td>{!! $connectionMessage !!}</td>
                    <td>{!! $connectionResult !!}</td>
                </tr>
                @if (isset($databaseVersionMessage))
                    <tr>
                        <td>{!! $databaseVersionMessage !!}</td>
                        <td>{!! $databaseVersionVersion !!}</td>
                    </tr>
                    <tr>
                        <td>{!! $databaseNameMessage !!}</td>
                        <td>{!! $databaseNameName !!}</td>
                    </tr>
                @endif
                @foreach($errors as $error)
                    <tr>
                        <td>{!! $error['text'] !!}</td>
                        <td>{!! $error['icon'] !!}</td>
                    </tr>
                @endforeach
            </table>
            @endif
        </div>
    </tr>
    </tbody>
@endsection