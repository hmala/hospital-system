<?php

namespace App\Http\Controllers;

use App\Models\MedicalDevice;
use Illuminate\Http\Request;

class MedicalDeviceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:manage medical devices')->except(['index']);
        $this->middleware('permission:view medical devices')->only(['index']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = MedicalDevice::withCount('surgeries');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('serial_number', 'like', '%' . $search . '%')
                  ->orWhere('type', 'like', '%' . $search . '%')
                  ->orWhere('supplier', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $devices = $query->orderBy('name', 'asc')->paginate(10)->withQueryString();

        return view('medical-devices.index', compact('devices'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('medical-devices.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'supplier' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive,maintenance',
            'serial_number' => 'nullable|string|max:255|unique:medical_devices,serial_number',
            'last_maintenance_at' => 'nullable|date',
            'purchase_date' => 'nullable|date',
        ]);

        MedicalDevice::create($validated);

        return redirect()->route('medical-devices.index')
            ->with('success', 'تم إضافة الجهاز الطبي بنجاح');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MedicalDevice $medicalDevice)
    {
        return view('medical-devices.edit', compact('medicalDevice'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MedicalDevice $medicalDevice)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'supplier' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive,maintenance',
            'serial_number' => 'nullable|string|max:255|unique:medical_devices,serial_number,' . $medicalDevice->id,
            'last_maintenance_at' => 'nullable|date',
            'purchase_date' => 'nullable|date',
        ]);

        $medicalDevice->update($validated);

        return redirect()->route('medical-devices.index')
            ->with('success', 'تم تحديث بيانات الجهاز الطبي بنجاح');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MedicalDevice $medicalDevice)
    {
        if ($medicalDevice->surgeries()->count() > 0) {
            return redirect()->route('medical-devices.index')
                ->with('error', 'لا يمكن حذف الجهاز لأنه مرتبط بعمليات جراحية مسجلة. يمكنك تعطيله بدلاً من ذلك.');
        }

        $medicalDevice->delete();

        return redirect()->route('medical-devices.index')
            ->with('success', 'تم حذف الجهاز الطبي بنجاح');
    }
}
