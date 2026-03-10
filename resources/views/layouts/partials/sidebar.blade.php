@php
/**
 * Build sidebar from dynamic links stored in database.
 * Each link may specify roles and/or permission.
 */
$user = Auth::user();
$links = \App\Models\SidebarLink::orderBy('order')->get();

function renderLink($link, $user) {
    if (!$link->isVisibleTo($user)) {
        return '';
    }
    $route = $link->route ? route($link->route) : '#';
    $active = $link->route && request()->routeIs($link->route.'*') ? 'active' : '';
    $badge = '';
    // future: support badge count via model callback
    return "<li class='nav-item'>".
           "<a class='nav-link {$active}' href='{$route}'>".
           "<i class='{$link->icon}'></i><span> {$link->title}</span>".
           "</a></li>";
}
@endphp

<ul class="nav flex-column p-3">
    @foreach($links as $link)
        {!! renderLink($link, $user) !!}
    @endforeach
</ul>
