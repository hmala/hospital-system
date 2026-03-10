<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoomController extends Controller
{
    /**
     * عرض قائمة الغرف
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->hasRole(['admin', 'receptionist', 'staff', 'surgery_staff'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $query = Room::query();

        // تصفية حسب النوع
        if ($request->filled('type')) {
            $query->where('room_type', $request->type);
        }

        // تصفية حسب الحالة
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // تصفية حسب الطابق
        if ($request->filled('floor')) {
            $query->where('floor', $request->floor);
        }

        // البحث
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('room_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $rooms = $query->orderBy('floor')->orderBy('room_number')->get();

        // إحصائيات
        $stats = [
            'total' => Room::count(),
            'available' => Room::where('status', 'available')->where('is_active', true)->count(),
            'occupied' => Room::where('status', 'occupied')->count(),
            'maintenance' => Room::where('status', 'maintenance')->count(),
            'regular' => Room::where('room_type', 'regular')->where('is_active', true)->count(),
            'vip' => Room::where('room_type', 'vip')->where('is_active', true)->count(),
        ];

        // قائمة الطوابق للتصفية
        $floors = Room::whereNotNull('floor')->distinct()->pluck('floor');

        return view('rooms.index', compact('rooms', 'stats', 'floors'));
    }

    /**
     * عرض نموذج إضافة غرفة جديدة
     */
    public function create()
    {
        $user = Auth::user();
        
        if (!$user->hasRole(['admin'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        return view('rooms.create');
    }

    /**
     * حفظ غرفة جديدة
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->hasRole(['admin'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $validated = $request->validate([
            'room_number' => 'required|string|max:50|unique:rooms,room_number',
            'room_type' => 'required|in:regular,vip',
            'floor' => 'nullable|string|max:50',
            'daily_fee' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:500',
            'beds_count' => 'required|integer|min:1|max:10',
            'has_bathroom' => 'boolean',
            'has_tv' => 'boolean',
            'has_ac' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $validated['has_bathroom'] = $request->boolean('has_bathroom');
        $validated['has_tv'] = $request->boolean('has_tv');
        $validated['has_ac'] = $request->boolean('has_ac');
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['status'] = 'available';

        Room::create($validated);

        return redirect()->route('rooms.index')
            ->with('success', 'تم إضافة الغرفة بنجاح');
    }

    /**
     * عرض تفاصيل غرفة
     */
    public function show(Room $room)
    {
        $user = Auth::user();
        
        if (!$user->hasRole(['admin', 'receptionist', 'staff', 'surgery_staff'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        // جلب العمليات المرتبطة بهذه الغرفة
        $surgeries = $room->surgeries()
            ->with(['patient.user', 'doctor.user'])
            ->orderBy('scheduled_date', 'desc')
            ->take(10)
            ->get();

        return view('rooms.show', compact('room', 'surgeries'));
    }

    /**
     * عرض نموذج تعديل غرفة
     */
    public function edit(Room $room)
    {
        $user = Auth::user();
        
        if (!$user->hasRole(['admin'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        return view('rooms.edit', compact('room'));
    }

    /**
     * تحديث غرفة
     */
    public function update(Request $request, Room $room)
    {
        $user = Auth::user();
        
        if (!$user->hasRole(['admin'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $validated = $request->validate([
            'room_number' => 'required|string|max:50|unique:rooms,room_number,' . $room->id,
            'room_type' => 'required|in:regular,vip',
            'floor' => 'nullable|string|max:50',
            'daily_fee' => 'required|numeric|min:0',
            'status' => 'required|in:available,occupied,maintenance',
            'description' => 'nullable|string|max:500',
            'beds_count' => 'required|integer|min:1|max:10',
            'has_bathroom' => 'boolean',
            'has_tv' => 'boolean',
            'has_ac' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $validated['has_bathroom'] = $request->boolean('has_bathroom');
        $validated['has_tv'] = $request->boolean('has_tv');
        $validated['has_ac'] = $request->boolean('has_ac');
        $validated['is_active'] = $request->boolean('is_active', true);

        $room->update($validated);

        return redirect()->route('rooms.index')
            ->with('success', 'تم تحديث الغرفة بنجاح');
    }

    /**
     * حذف غرفة
     */
    public function destroy(Room $room)
    {
        $user = Auth::user();
        
        if (!$user->hasRole(['admin'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        // التحقق من عدم وجود عمليات مرتبطة
        if ($room->surgeries()->exists()) {
            return redirect()->route('rooms.index')
                ->with('error', 'لا يمكن حذف الغرفة لوجود عمليات مرتبطة بها');
        }

        $room->delete();

        return redirect()->route('rooms.index')
            ->with('success', 'تم حذف الغرفة بنجاح');
    }

    /**
     * تغيير حالة الغرفة بسرعة
     */
    public function changeStatus(Request $request, Room $room)
    {
        $user = Auth::user();
        
        if (!$user->hasRole(['admin', 'receptionist', 'staff', 'surgery_staff'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $validated = $request->validate([
            'status' => 'required|in:available,occupied,maintenance',
        ]);

        $room->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => 'تم تغيير حالة الغرفة بنجاح',
            'status' => $room->status,
            'status_name' => $room->status_name,
            'status_color' => $room->status_color,
        ]);
    }

    /**
     * الحصول على الغرف المتاحة (للاستخدام في AJAX)
     */
    public function getAvailable(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->hasRole(['admin', 'receptionist', 'staff', 'surgery_staff', 'doctor'])) {
            return response()->json(['error' => 'غير مصرح لك بالوصول'], 403);
        }

        $rooms = Room::available()
            ->orderBy('room_type')
            ->orderBy('room_number')
            ->get()
            ->map(function($room) {
                return [
                    'id' => $room->id,
                    'room_number' => $room->room_number,
                    'room_type' => $room->room_type,
                    'room_type_name' => $room->room_type_name,
                    'daily_fee' => $room->daily_fee,
                    'floor' => $room->floor,
                    'beds_count' => $room->beds_count,
                    'features' => $room->features_text,
                ];
            });

        return response()->json($rooms);
    }
}
