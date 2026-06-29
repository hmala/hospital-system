<?php
$roles = Spatie\Permission\Models\Role::pluck('name')->toArray();
echo "Available roles: " . implode(', ', $roles) . "\n";
echo "Has 'resident': " . (in_array('resident', $roles) ? 'YES' : 'NO') . "\n";
