<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * عرض جميع الإشعارات
     */
    public function index()
    {
        $user = Auth::user();
        
        // جلب الإشعارات الخاصة بالمستخدم
        $notifications = Notification::where('notifiable_type', 'App\\Models\\User')
            ->where('notifiable_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    /**
     * تحديد إشعار كمقروء
     */
    public function markAsRead(Notification $notification)
    {
        $notification->markAsRead();
        
        return response()->json(['success' => true]);
    }

    /**
     * تحديد جميع الإشعارات كمقروءة
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        
        Notification::where('notifiable_type', 'App\\Models\\User')
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return redirect()->back()->with('success', 'تم تحديد جميع الإشعارات كمقروءة');
    }

    /**
     * حذف إشعار
     */
    public function destroy(Notification $notification)
    {
        $notification->delete();
        
        return redirect()->back()->with('success', 'تم حذف الإشعار');
    }

    /**
     * الحصول على عدد الإشعارات غير المقروءة (API)
     */
    public function getUnreadCount()
    {
        $user = Auth::user();
        $count = Notification::unreadCountForUser($user->id);
        
        return response()->json(['count' => $count]);
    }
}
