<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at'
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime'
    ];

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The data type of the auto-incrementing ID.
     */
    protected $keyType = 'string';

    /**
     * Boot function to generate UUID
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    /**
     * الحصول على العنوان من البيانات
     */
    public function getTitleAttribute()
    {
        return $this->data['title'] ?? '';
    }

    /**
     * الحصول على الرسالة من البيانات
     */
    public function getMessageAttribute()
    {
        return $this->data['message'] ?? '';
    }

    /**
     * الحصول على المستخدم المرتبط
     */
    public function user()
    {
        return $this->morphTo('notifiable');
    }

    /**
     * تحديد الإشعار كمقروء
     */
    public function markAsRead()
    {
        $this->update(['read_at' => now()]);
    }

    /**
     * التحقق إذا كان الإشعار مقروء
     */
    public function isRead()
    {
        return !is_null($this->read_at);
    }

    /**
     * الحصول على الإشعارات غير المقروءة لمستخدم
     */
    public static function unreadForUser($userId)
    {
        return self::where('notifiable_type', 'App\Models\User')
            ->where('notifiable_id', $userId)
            ->whereNull('read_at')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * عدد الإشعارات غير المقروءة
     */
    public static function unreadCountForUser($userId)
    {
        return self::where('notifiable_type', 'App\Models\User')
            ->where('notifiable_id', $userId)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * إنشاء إشعار جديد
     */
    public static function createForUser($userId, $type, $title, $message, $data = [])
    {
        return self::create([
            'type' => $type,
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $userId,
            'data' => array_merge([
                'title' => $title,
                'message' => $message
            ], $data)
        ]);
    }

    /**
     * إنشاء إشعار لجميع المستخدمين بدور معين
     */
    public static function createForRole($role, $type, $title, $message, $data = [])
    {
        $users = \App\Models\User::role($role)->get();
        
        foreach ($users as $user) {
            self::createForUser($user->id, $type, $title, $message, $data);
        }
    }
}
