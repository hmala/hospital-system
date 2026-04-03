<?php

namespace App\Http\Controllers;

use App\Models\Incubator;
use App\Models\Room;
use Illuminate\Http\Request;

class IncubatorController extends Controller
{
    /**
     * عرض قائمة الحاضنات
     */
    public function index()
    {
        $user = auth()->user();
        
        if (!$user->hasAnyRole(['admin', 'receptionist', 'doctor', 'nicu_staff'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $incubators = Incubator::with(['room', 'activeReservation.patient.user'])
                               ->orderBy('incubator_number')
                               ->get();

        // Rooms for incubators (to show counters/indicators per room)
        $rooms = Room::where('is_active', true)
                     ->where('room_purpose', 'incubators')
                     ->withCount([
                         'incubators',
                         'incubators as occupied_incubators_count' => function ($query) {
                             $query->where('status', Incubator::STATUS_OCCUPIED);
                         }
                     ])
                     ->orderBy('room_number')
                     ->get();

        // إحصائيات
        $stats = [
            'total' => $incubators->count(),
            'available' => $incubators->where('status', Incubator::STATUS_AVAILABLE)->count(),
            'occupied' => $incubators->where('status', Incubator::STATUS_OCCUPIED)->count(),
            'maintenance' => $incubators->where('status', Incubator::STATUS_MAINTENANCE)->count(),
            'normal' => $incubators->where('incubator_type', Incubator::TYPE_NORMAL)->count(),
            'oxygen' => $incubators->where('incubator_type', Incubator::TYPE_OXYGEN)->count(),
            'phototherapy' => $incubators->where('incubator_type', Incubator::TYPE_PHOTOTHERAPY)->count(),
        ];

        return view('incubators.index', compact('incubators', 'stats', 'rooms'));
    }

    /**
     * عرض نموذج إنشاء حاضنة جديدة
     */
    public function create()
    {
        $user = auth()->user();
        
        if (!$user->hasRole('admin')) {
            abort(403, 'غير مصرح لك بإنشاء حاضنات');
        }

        $rooms = Room::where('is_active', true)->where('room_purpose', 'incubators')->get();

        return view('incubators.create', compact('rooms'));
    }

    /**
     * حفظ حاضنة جديدة
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        
        if (!$user->hasRole('admin')) {
            abort(403);
        }

        $validated = $request->validate([
            'incubator_number' => 'required|string|unique:incubators,incubator_number',
            'incubator_type' => 'required|in:normal,oxygen,phototherapy',
            'room_id' => 'nullable|exists:rooms,id',
            'daily_fee' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $incubator = Incubator::create($validated);

        return redirect()->route('incubators.index')
                        ->with('success', 'تم إنشاء الحاضنة بنجاح');
    }

    /**
     * عرض تفاصيل حاضنة معينة
     */
    public function show(Incubator $incubator)
    {
        $user = auth()->user();
        
        if (!$user->hasAnyRole(['admin', 'receptionist', 'doctor', 'nicu_staff'])) {
            abort(403);
        }

        $incubator->load(['room', 'reservations.patient.user', 'reservations.doctor.user']);

        return view('incubators.show', compact('incubator'));
    }

    /**
     * عرض نموذج تعديل حاضنة
     */
    public function edit(Incubator $incubator)
    {
        $user = auth()->user();
        
        if (!$user->hasRole('admin')) {
            abort(403);
        }

        $rooms = Room::where('is_active', true)->where('room_purpose', 'incubators')->get();

        return view('incubators.edit', compact('incubator', 'rooms'));
    }

    /**
     * تحديث بيانات حاضنة
     */
    public function update(Request $request, Incubator $incubator)
    {
        $user = auth()->user();
        
        if (!$user->hasRole('admin')) {
            abort(403);
        }

        $validated = $request->validate([
            'incubator_number' => 'required|string|unique:incubators,incubator_number,' . $incubator->id,
            'incubator_type' => 'required|in:normal,oxygen,phototherapy',
            'status' => 'required|in:available,occupied,maintenance',
            'room_id' => 'nullable|exists:rooms,id',
            'daily_fee' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $incubator->update($validated);

        return redirect()->route('incubators.index')
                        ->with('success', 'تم تحديث الحاضنة بنجاح');
    }

    /**
     * حذف حاضنة
     */
    public function destroy(Incubator $incubator)
    {
        $user = auth()->user();
        
        if (!$user->hasRole('admin')) {
            abort(403);
        }

        // التحقق من عدم وجود حجوزات نشطة
        if ($incubator->activeReservation()->exists()) {
            return redirect()->back()
                           ->with('error', 'لا يمكن حذف حاضنة لديها حجز نشط');
        }

        $incubator->delete();

        return redirect()->route('incubators.index')
                        ->with('success', 'تم حذف الحاضنة بنجاح');
    }

    /**
     * تبديل حالة الحاضنة (صيانة/متاحة)
     */
    public function toggleMaintenance(Incubator $incubator)
    {
        $user = auth()->user();
        
        if (!$user->hasAnyRole(['admin', 'nicu_staff'])) {
            abort(403);
        }

        if ($incubator->status === Incubator::STATUS_MAINTENANCE) {
            $incubator->update(['status' => Incubator::STATUS_AVAILABLE]);
            $message = 'تم إنهاء الصيانة وتفعيل الحاضنة';
        } else {
            $incubator->update(['status' => Incubator::STATUS_MAINTENANCE]);
            $message = 'تم تحويل الحاضنة إلى وضع الصيانة';
        }

        return redirect()->back()->with('success', $message);
    }
}
