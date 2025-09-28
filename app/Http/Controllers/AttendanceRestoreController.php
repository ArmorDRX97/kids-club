<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AttendanceRestoreController extends Controller
{
    public function restore(Request $request, Attendance $attendance): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        if (!$attendance->canBeRestored()) {
            return response()->json([
                'success' => false,
                'message' => 'Это посещение не может быть возвращено'
            ], 400);
        }

        $user = $request->user();
        $reason = $request->input('reason');

        if ($attendance->restore($user, $reason)) {
            // Логируем действие
            ActivityLogger::log($user, 'child.visit_restored', $attendance->child, [
                'attendance_id' => $attendance->id,
                'section_id' => $attendance->section_id,
                'section_name' => $attendance->section->name,
                'attended_on' => $attendance->attended_on->format('Y-m-d'),
                'reason' => $reason,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Посещение успешно возвращено'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Ошибка при возврате посещения'
        ], 500);
    }
}
