<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;

class UserController extends Controller
{
    //validate student using matric number
    public function validateStudent(Request $request)
    {
        try {
            $request->validate([
                'matric_no' => 'required|string'
            ]);

            $student = Student::where('matric_no', $request->matric_no)->first();

            if (!$student) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Student not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Student found',
                'data' => $student
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
