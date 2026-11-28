<?php

namespace App\Observers;

use App\Models\BedReservation;
use App\Models\Room;
use App\Models\Surgery;

class BedReservationObserver
{
    /**
     * عند إنشاء حجز جديد
     */
    public function created(BedReservation $reservation): void
    {
        $this->updateRoomStatus($reservation);
    }

    /**
     * عند تحديث الحجز
     */
    public function updated(BedReservation $reservation): void
    {
        $this->updateRoomStatus($reservation);
        
        // إذا تغيرت الغرفة، يجب تحديث الغرفة القديمة أيضاً
        if ($reservation->isDirty('room_id') && $reservation->getOriginal('room_id')) {
            $this->syncRoomStatus($reservation->getOriginal('room_id'));
        }
    }

    /**
     * عند حذف الحجز
     */
    public function deleted(BedReservation $reservation): void
    {
        if ($reservation->room_id) {
            $this->syncRoomStatus($reservation->room_id);
        }
    }

    /**
     * تحديث حالة الغرفة بناءً على الحجز
     */
    private function updateRoomStatus(BedReservation $reservation): void
    {
        if (!$reservation->room_id) {
            return;
        }

        $this->syncRoomStatus($reservation->room_id);
    }

    /**
     * مزامنة حالة غرفة معينة
     */
    public static function syncRoomStatus(int $roomId): void
    {
        $room = Room::find($roomId);
        
        if (!$room) {
            return;
        }

        // التحقق من وجود حجوزات نشطة أو مريض عملية جراحية في هذه الغرفة
        $hasActiveReservations = BedReservation::where('room_id', $roomId)
            ->where('status', 'confirmed')
            ->exists();

        $hasActiveSurgery = Surgery::where('room_id', $roomId)
            ->whereNull('discharged_at')
            ->whereNull('medical_discharged_at')
            ->where('status', '!=', 'cancelled')
            ->exists();

        $isOccupied = $hasActiveReservations || $hasActiveSurgery;

        // تحديث حالة الغرفة
        $newStatus = $isOccupied ? 'occupied' : 'available';
        
        // تحديث فقط إذا كانت الحالة مختلفة (وليست صيانة)
        if ($room->status !== 'maintenance' && $room->status !== $newStatus) {
            $room->update(['status' => $newStatus]);
        }
    }

    /**
     * مزامنة جميع الغرف
     */
    public static function syncAllRooms(): array
    {
        $rooms = Room::where('status', '!=', 'maintenance')->get();
        $updated = 0;

        foreach ($rooms as $room) {
            $hasActiveReservations = BedReservation::where('room_id', $room->id)
                ->where('status', 'confirmed')
                ->exists();

            $hasActiveSurgery = Surgery::where('room_id', $room->id)
                ->whereNull('discharged_at')
                ->whereNull('medical_discharged_at')
                ->where('status', '!=', 'cancelled')
                ->exists();

            $isOccupied = $hasActiveReservations || $hasActiveSurgery;
            $newStatus = $isOccupied ? 'occupied' : 'available';
            
            if ($room->status !== $newStatus) {
                $room->update(['status' => $newStatus]);
                $updated++;
            }
        }

        return [
            'total' => $rooms->count(),
            'updated' => $updated,
        ];
    }
}
