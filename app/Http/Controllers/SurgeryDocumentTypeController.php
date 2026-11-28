<?php

namespace App\Http\Controllers;

use App\Models\SurgeryDocumentType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SurgeryDocumentTypeController extends Controller
{
    public function index()
    {
        $types = SurgeryDocumentType::active()->get();

        return response()->json([
            'success' => true,
            'types' => $types,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150',
        ]);

        $name = trim($request->name);

        $type = SurgeryDocumentType::firstOrCreate(
            ['name' => $name],
            [
                'is_active' => true,
                'created_by' => Auth::id(),
                'sort_order' => (SurgeryDocumentType::max('sort_order') ?? 0) + 1,
            ]
        );

        if (! $type->is_active) {
            $type->update(['is_active' => true]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تمت إضافة نوع المستند بنجاح',
            'type' => $type,
        ]);
    }

    public function destroy(SurgeryDocumentType $type)
    {
        if ($type->documents()->exists()) {
            $type->update(['is_active' => false]);
        } else {
            $type->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'تم حذف/تعطيل نوع المستند بنجاح',
        ]);
    }
}
