<?php

namespace App\Http\Controllers;

use App\Models\SidebarLink;
use Illuminate\Http\Request;

class SidebarLinkController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','role:admin']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $links = SidebarLink::orderBy('order')->get();
        return view('sidebar-links.index', compact('links'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = \Spatie\Permission\Models\Role::pluck('name','name');
        return view('sidebar-links.create', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'route' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'roles' => 'nullable|array',
            'roles.*' => 'string',
            'permission' => 'nullable|string|max:255',
            'order' => 'nullable|integer',
            'enabled' => 'boolean',
        ]);
        $data['roles'] = $data['roles'] ?? [];
        SidebarLink::create($data);
        return redirect()->route('sidebar-links.index')->with('success', 'Link created');
    }

    /**
     * Display the specified resource.
     */
    public function show(SidebarLink $sidebarLink)
    {
        return redirect()->route('sidebar-links.edit', $sidebarLink);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SidebarLink $sidebarLink)
    {
        $roles = \Spatie\Permission\Models\Role::pluck('name','name');
        return view('sidebar-links.edit', compact('sidebarLink', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SidebarLink $sidebarLink)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'route' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'roles' => 'nullable|array',
            'roles.*' => 'string',
            'permission' => 'nullable|string|max:255',
            'order' => 'nullable|integer',
            'enabled' => 'boolean',
        ]);
        $data['roles'] = $data['roles'] ?? [];
        $sidebarLink->update($data);
        return redirect()->route('sidebar-links.index')->with('success', 'Link updated');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SidebarLink $sidebarLink)
    {
        $sidebarLink->delete();
        return redirect()->route('sidebar-links.index')->with('success', 'Link removed');
    }}
