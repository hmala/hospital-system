<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SidebarLink extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'title',
        'route',
        'icon',
        'roles',
        'permission',
        'order',
        'enabled',
    ];

    protected $casts = [
        'roles' => 'array',
        'enabled' => 'boolean',
    ];

    // check visibility for a user
    public function isVisibleTo($user)
    {
        if (!$this->enabled) {
            return false;
        }
        if ($this->permission && !$user->can($this->permission)) {
            return false;
        }
        if (!empty($this->roles) && is_array($this->roles)) {
            return $user->hasAnyRole($this->roles);
        }
        return true;
    }
}
