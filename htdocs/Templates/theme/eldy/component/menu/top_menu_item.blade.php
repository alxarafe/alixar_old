<!-- Begin: theme/eldy/component/menu/top_menu_item.blade.php -->
{{--
Insert a top menu option.

How to use:

@include('components/input_number',[
    'name' => 'home',
    'href' => 'https://alixar/index.php'
    'prefix' => '',
    'title' => 'inicio',
    'selected' => true,
])

The selected field is optional. By default false.
--}}
@php
    $id = 'mainmenutd_' . $name;
    $selected = isset($selected) && $selected ? 'tmenusel' : 'tmenu';
@endphp
<li class="{!! $selected !!}" id="{!! $id !!}">
    <div class="tmenucenter">
        <a class="tmenuimage {!! $selected !!}" tabindex="-1" href="https://alixar/index.php?mainmenu=home&amp;leftmenu=home" title="{!! $title !!}">
            <div class="mainmenu home topmenuimage">{!! $prefix !!}</div>
        </a><a class="tmenulabel {!! $selected !!}" id="mainmenua_{!! $name !!}" href="https://alixar/index.php?mainmenu=home&amp;leftmenu=home" title="{!! $title !!}"><span class="mainmenuaspan">{!! $title !!}</span></a>
    </div>
</li>
<!-- End: theme/eldy/component/menu/top_menu_item.blade.php -->
