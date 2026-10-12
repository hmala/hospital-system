<?php

namespace App\Http\Controllers;

use App\Services\DatabaseSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class DatabaseSyncController extends Controller
{
    protected function authorizeAccess(): void
    {
        $user = auth()->user();
        if (!$user) {
            abort(403, 'غير مصرح.');
        }

        $isAdmin = $user->hasAnyRole(['admin', 'super_admin', 'hospital_admin', 'مدير النظام']);
        if (!$isAdmin && (!$user->can('manage database sync') && !$user->can('manage settings'))) {
            abort(403, 'غير مصرح لك بالوصول إلى مركز مزامنة وسحب قواعد البيانات.');
        }
    }

    public function index(DatabaseSyncService $syncService)
    {
        $this->authorizeAccess();

        $connectionInfo = [
            'host' => config('database.connections.remote_hostinger.host'),
            'port' => config('database.connections.remote_hostinger.port'),
            'database' => config('database.connections.remote_hostinger.database'),
            'username' => config('database.connections.remote_hostinger.username'),
            'local_database' => config('database.connections.mysql.database'),
        ];

        // قائمة النسخ الاحتياطية المحلية
        $backupDir = storage_path('app/backups');
        $backups = [];
        if (File::exists($backupDir)) {
            $files = File::files($backupDir);
            foreach ($files as $file) {
                if ($file->getExtension() === 'sql') {
                    $backups[] = [
                        'name' => $file->getFilename(),
                        'size' => round($file->getSize() / 1024 / 1024, 2) . ' MB',
                        'date' => date('Y-m-d H:i:s', $file->getMTime()),
                    ];
                }
            }
            usort($backups, fn($a, $b) => strcmp($b['date'], $a['date']));
        }

        return view('system.database-sync', compact('connectionInfo', 'backups'));
    }

    public function test(DatabaseSyncService $syncService)
    {
        $this->authorizeAccess();
        $result = $syncService->testRemoteConnection();
        return response()->json($result);
    }

    public function pull(DatabaseSyncService $syncService)
    {
        $this->authorizeAccess();

        try {
            $result = $syncService->pullFromRemote();
            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء السحب: ' . $e->getMessage(),
            ], 500);
        }
    }
}
