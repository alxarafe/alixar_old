@extends('install/master/install_layout')

@section('body')
    <tbody>
    <input type="hidden" name="action" value="finish">
    <input type="hidden" name="language" value="{!! $me->config->main->language!!}">
    <h3>
        <img class="valignmiddle inline-block paddingright"
             src="{!! $me->config->main->url !!}/Templates/common/octicons/build/svg/database.svg"
             width="20" alt="Database">{!! $me->langs->trans("DatabaseMigration") !!}
    </h3>
    @if ($me->vars->config_read_only)
        <h5>{!! $me->langs->trans("ConfFileIsNotWritable", $me->vars->config_filename) !!}</h5>
    @endif
    <table cellspacing="0" style="padding: 4px 4px 4px 0" border="0" width="100%">
        @foreach($me->vars->checks as $check)
            <tr>
                <td>{!! $check['text'] !!}</td>
                <td>
                    <img
                        src="{!! $me->config->main->url !!}/Templates/theme/{!!  $me->config->main->theme !!}/img/{!! $check['icon'] !!}.png"
                        alt="{!! ucfirst($check['icon']) !!}" class="valignmiddle">
                </td>
            </tr>
        @endforeach
    </table>
    </tbody>
@endsection
