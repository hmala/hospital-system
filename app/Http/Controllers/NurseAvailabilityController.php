<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\NurseAvailability;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NurseAvailabilityController extends Controller
{
    public const FLOORS = [
        'الطابق الثاني',
        'الطابق الثالث',
        'الطابق الرابع',
        'الطابق الخامس',
        'الطابق السادس',
        'الطابق السابع',
        'صالة العمليات',
        'العناية المركزة (ICU)',
        'طوارئ التمريض',
        'الخدج (NICU)',
    ];

    public const SHIFTS = [
        'morning' => 'صباحي',
        'evening' => 'مسائي',
        'night' => 'ليلي',
        'full_day' => 'يوم كامل',
    ];

    /**
     * Check if user is authorized to manage nurse availability.
     */
    protected function authorizeManager(): void
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin']);

        if (! $isAdmin && (! $user || (! $user->can('manage nurse availability') && ! $user->hasRole('head_nurse')))) {
            abort(403, 'غير مصرح لك بالوصول إلى إدارة جدول دوام التمريض.');
        }
    }

    /**
     * Display nurse availability schedule and roster.
     */
    public function index(Request $request): View
    {
        $this->authorizeManager();

        $selectedDate = $request->query('date', today()->toDateString());
        $search = trim($request->query('search', ''));
        $floorFilter = $request->query('floor');
        $statusFilter = $request->query('status', 'all');

        $nursesQuery = User::where(function ($q) {
            $q->whereHas('roles', function ($rq) {
                $rq->whereIn('name', ['nurse', 'nicu_nurse', 'icu_nurse', 'head_nurse']);
            })->orWhere('role', 'nurse');
        })
            ->where('is_active', true)
            ->with(['nurseAvailabilities' => function ($q) use ($selectedDate) {
                $q->whereDate('date', $selectedDate);
            }]);

        if (! empty($search)) {
            $nursesQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $allNurses = $nursesQuery->orderBy('name')->get();

        // Calculate statistics
        $totalNurses = $allNurses->count();
        $availableCount = $allNurses->filter(function ($nurse) {
            $record = $nurse->nurseAvailabilities->first();

            return $record && $record->is_available;
        })->count();

        $offDutyCount = $totalNurses - $availableCount;
        $coverageRate = $totalNurses > 0 ? (int) round(($availableCount / $totalNurses) * 100) : 0;

        // Apply in-memory status and floor filtering for table display
        $filteredNurses = $allNurses->filter(function ($nurse) use ($statusFilter, $floorFilter) {
            $record = $nurse->nurseAvailabilities->first();
            $isAvailable = $record ? (bool) $record->is_available : false;
            $assignedFloor = $record?->assigned_floor;

            if ($statusFilter === 'available' && ! $isAvailable) {
                return false;
            }
            if ($statusFilter === 'off_duty' && $isAvailable) {
                return false;
            }
            if (! empty($floorFilter) && $assignedFloor !== $floorFilter) {
                return false;
            }

            return true;
        });

        // Floor breakdown counts
        $floorCounts = [];
        foreach (self::FLOORS as $fl) {
            $floorCounts[$fl] = $allNurses->filter(function ($nurse) use ($fl) {
                $record = $nurse->nurseAvailabilities->first();

                return $record && $record->is_available && $record->assigned_floor === $fl;
            })->count();
        }

        return view('nurse-availability.index', [
            'nurses' => $filteredNurses,
            'selectedDate' => $selectedDate,
            'search' => $search,
            'floorFilter' => $floorFilter,
            'statusFilter' => $statusFilter,
            'floors' => self::FLOORS,
            'shifts' => self::SHIFTS,
            'totalNurses' => $totalNurses,
            'availableCount' => $availableCount,
            'offDutyCount' => $offDutyCount,
            'coverageRate' => $coverageRate,
            'floorCounts' => $floorCounts,
        ]);
    }

    /**
     * Update availability status for a specific nurse (AJAX toggle).
     */
    public function updateAvailability(Request $request, User $nurse): JsonResponse
    {
        $this->authorizeManager();

        $request->validate([
            'is_available' => 'required',
            'date' => 'nullable|date',
        ]);

        $date = $request->input('date', today()->toDateString());
        $isAvailable = filter_var($request->input('is_available'), FILTER_VALIDATE_BOOLEAN);

        $record = NurseAvailability::where('user_id', $nurse->id)
            ->whereDate('date', $date)
            ->first();

        if ($record) {
            $record->update([
                'is_available' => $isAvailable,
                'created_by' => auth()->id(),
            ]);
        } else {
            $record = NurseAvailability::create([
                'user_id' => $nurse->id,
                'date' => $date,
                'is_available' => $isAvailable,
                'created_by' => auth()->id(),
            ]);
        }

        $statusText = $isAvailable ? 'متاح بالدوام' : 'خارج الدوام';

        return response()->json([
            'success' => true,
            'nurse_id' => $nurse->id,
            'is_available' => $isAvailable,
            'status_text' => $statusText,
            'assigned_floor' => $record->assigned_floor,
            'message' => "تم تحديث حالة الممرض {$nurse->name} إلى: {$statusText}",
        ]);
    }

    /**
     * Update assigned floor for a specific nurse.
     */
    public function updateFloor(Request $request, User $nurse): JsonResponse
    {
        $this->authorizeManager();

        $request->validate([
            'assigned_floor' => 'nullable|string|max:100',
            'date' => 'nullable|date',
        ]);

        $date = $request->input('date', today()->toDateString());
        $floor = $request->input('assigned_floor');

        $record = NurseAvailability::where('user_id', $nurse->id)
            ->whereDate('date', $date)
            ->first();

        if ($record) {
            $record->update([
                'assigned_floor' => $floor,
                'created_by' => auth()->id(),
            ]);
        } else {
            $record = NurseAvailability::create([
                'user_id' => $nurse->id,
                'date' => $date,
                'assigned_floor' => $floor,
                'created_by' => auth()->id(),
            ]);
        }

        // If updating for today, optionally sync location_id if floor matches a Location
        if ($date === today()->toDateString() && ! empty($floor)) {
            $location = Location::where('name', 'like', "%{$floor}%")->first();
            if ($location) {
                $nurse->update(['location_id' => $location->id]);
            }
        }

        return response()->json([
            'success' => true,
            'nurse_id' => $nurse->id,
            'assigned_floor' => $floor,
            'message' => "تم تعيين طابق الممرض {$nurse->name} إلى: ".($floor ?: 'غير محدد'),
        ]);
    }

    /**
     * Update assigned shift for a specific nurse.
     */
    public function updateShift(Request $request, User $nurse): JsonResponse
    {
        $this->authorizeManager();

        $request->validate([
            'shift' => 'required|in:morning,evening,night,full_day',
            'date' => 'nullable|date',
        ]);

        $date = $request->input('date', today()->toDateString());
        $shift = $request->input('shift');

        $record = NurseAvailability::where('user_id', $nurse->id)
            ->whereDate('date', $date)
            ->first();

        if ($record) {
            $record->update([
                'shift' => $shift,
                'created_by' => auth()->id(),
            ]);
        } else {
            $record = NurseAvailability::create([
                'user_id' => $nurse->id,
                'date' => $date,
                'shift' => $shift,
                'created_by' => auth()->id(),
            ]);
        }

        $shiftName = self::SHIFTS[$shift] ?? $shift;

        return response()->json([
            'success' => true,
            'nurse_id' => $nurse->id,
            'shift' => $shift,
            'message' => "تم تحديث شفت الممرض {$nurse->name} إلى: {$shiftName}",
        ]);
    }

    /**
     * Bulk update all nurses for the selected date.
     */
    public function bulkUpdate(Request $request): RedirectResponse
    {
        $this->authorizeManager();

        $request->validate([
            'action' => 'required|in:set_all_available,set_all_off_duty',
            'date' => 'nullable|date',
            'floor' => 'nullable|string',
        ]);

        $date = $request->input('date', today()->toDateString());
        $action = $request->input('action');
        $floorFilter = $request->input('floor');
        $isAvailable = ($action === 'set_all_available');

        $nursesQuery = User::where(function ($q) {
            $q->whereHas('roles', function ($rq) {
                $rq->whereIn('name', ['nurse', 'nicu_nurse', 'icu_nurse', 'head_nurse']);
            })->orWhere('role', 'nurse');
        })->where('is_active', true);

        $nurses = $nursesQuery->get();

        foreach ($nurses as $nurse) {
            $record = NurseAvailability::where('user_id', $nurse->id)
                ->whereDate('date', $date)
                ->first();

            $data = [
                'is_available' => $isAvailable,
                'created_by' => auth()->id(),
            ];

            if (! empty($floorFilter)) {
                $data['assigned_floor'] = $floorFilter;
            }

            if ($record) {
                $record->update($data);
            } else {
                $data['user_id'] = $nurse->id;
                $data['date'] = $date;
                NurseAvailability::create($data);
            }
        }

        $actionText = $isAvailable ? 'تحديد كافة الكادر التمريضي كمتاح بالدوام' : 'تحديد كافة الكادر التمريضي كخارج الدوام';

        return redirect()->route('nurse-availability.index', ['date' => $date])
            ->with('success', "تم بنجاح {$actionText} لتاريخ {$date}.");
    }

    /**
     * Dedicated Off-Duty Landing Screen for nurses who are not scheduled today.
     */
    public function offDuty(Request $request): View|RedirectResponse
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // If user is not restricted or is already marked as available today, redirect to normal workspace
        if ($user->isNurseAvailableToday() || $user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin', 'head_nurse']) || $user->can('manage nurse availability')) {
            return redirect()->route('nursing-station.index');
        }

        $todayRecord = $user->todayNurseAvailability;

        return view('nurse-availability.off-duty', [
            'nurse' => $user,
            'todayRecord' => $todayRecord,
            'todayDate' => today(),
        ]);
    }

    /**
     * Check if the supervisor has activated the nurse, and if so, grant entry.
     */
    public function checkAccess(Request $request): RedirectResponse
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->isNurseAvailableToday()) {
            return redirect()->route('nursing-station.index')
                ->with('success', 'أهلاً بك! تم تأكيد دوامك لليوم بنجاح.');
        }

        return redirect()->route('nurse.off-duty')
            ->with('warning', 'ما زال حسابك غير مفعل ضمن جدول دوام اليوم. يرجى التواصل مع مسؤول قسم التمريض لتفعيل دوامك.');
    }
}
