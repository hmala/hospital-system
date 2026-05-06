<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserLabTestStat extends Model
{
    use HasFactory;

    protected $table = 'user_lab_test_stats';

    protected $fillable = [
        'user_id',
        'lab_test_id',
        'is_favorite',
    ];

    protected $casts = [
        'is_favorite'  => 'boolean',
    ];

    // ────────────── العلاقات ──────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function labTest()
    {
        return $this->belongsTo(LabTest::class);
    }

    // ────────────── Helpers ──────────────

    /**
     * تبديل حالة المفضلة
     */
    public static function toggleFavorite(int $userId, int $labTestId): bool
    {
        $stat = static::firstOrCreate(
            ['user_id' => $userId, 'lab_test_id' => $labTestId],
            ['is_favorite' => false]
        );

        $stat->is_favorite = !$stat->is_favorite;
        $stat->save();

        return $stat->is_favorite;
    }

    /**
     * جلب المفضلات للمستخدم
     */
    public static function getFavoritesForUser(int $userId)
    {
        return static::where('user_id', $userId)
            ->where('is_favorite', true)
            ->with('labTest')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
