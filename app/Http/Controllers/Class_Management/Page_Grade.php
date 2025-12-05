<?php

namespace App\Http\Controllers\Class_Management;

use Illuminate\Http\Request;
use App\Http\Controllers\MainController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class Page_Grade extends MainController
{
    public function teacherIndex($classId)
    {
        $class = DB::table('classes')->where('id', $classId)->first();

        if (!$class) {
            abort(404, 'Class not found');
        }

        return view('modules.grade.page_gradebook', [
            'userType' => 'teacher',
            'class' => $class,
            'scripts' => ['class_grade/page_gradebook.js']
        ]);
    }

    public function studentIndex($classId)
    {
        $class = DB::table('classes')->where('id', $classId)->first();

        if (!$class) {
            abort(404, 'Class not found');
        }

        $studentNumber = Auth::guard('student')->user()->student_number;

        return view('modules.grade.page_grade', [
            'userType' => 'student',
            'class' => $class,
            'studentNumber' => $studentNumber,
            'scripts' => ['class_grade/page_grade.js']
        ]);
    }

    public function getStudentGrades($classId, $studentNumber)
    {
        // Verify the class exists
        $class = DB::table('classes')->where('id', $classId)->first();
        
        if (!$class) {
            return response()->json(['error' => 'Class not found'], 404);
        }

        // Verify the student is enrolled in this class
        $isEnrolled = DB::table('student_class_matrix')
            ->where('student_number', $studentNumber)
            ->where('class_code', $class->class_code)
            ->exists();

        if (!$isEnrolled) {
            $student = DB::table('students')->where('student_number', $studentNumber)->first();
            
            if ($student && $student->section_id) {
                $isEnrolledThroughSection = DB::table('section_class_matrix')
                    ->where('section_id', $student->section_id)
                    ->where('class_id', $classId)
                    ->exists();
                
                if (!$isEnrolledThroughSection) {
                    return response()->json(['error' => 'Student not enrolled in this class'], 403);
                }
            } else {
                return response()->json(['error' => 'Student not enrolled in this class'], 403);
            }
        }

        // Get current semester info
        $currentSemester = DB::table('semesters')
            ->where('status', 'active')
            ->first();

        // Get quarters for current semester
        $quarters = DB::table('quarters')
            ->where('semester_id', $currentSemester->id ?? 0)
            ->orderBy('order_number')
            ->get();

        // Get all lessons and quizzes with quarter info
        $lessons = DB::table('lessons as l')
            ->where('l.class_id', $classId)
            ->where('l.status', 1)
            ->orderBy('l.order_number')
            ->get();

        $gradesData = [];

        foreach ($lessons as $lesson) {
            // Get quizzes for this lesson
            $quizzes = DB::table('quizzes as q')
                ->leftJoin('quarters as qr', 'q.quarter_id', '=', 'qr.id')
                ->where('q.lesson_id', $lesson->id)
                ->where('q.status', 1)
                ->select(
                    'q.id as quiz_id',
                    'q.title as quiz_title',
                    'q.passing_score',
                    'q.available_from',
                    'q.available_until',
                    'q.max_attempts',
                    'q.quarter_id',
                    'qr.name as quarter_name',
                    'qr.code as quarter_code'
                )
                ->orderBy('q.id')
                ->get();

            // Get lecture progress for this lesson
            $lectureProgress = DB::table('student_lecture_progress')
                ->where('student_number', $studentNumber)
                ->where('lesson_id', $lesson->id)
                ->where('class_id', $classId)
                ->get();

            $totalLectures = DB::table('lectures')
                ->where('lesson_id', $lesson->id)
                ->where('status', 1)
                ->count();

            $completedLectures = $lectureProgress->where('is_completed', 1)->count();

            $lessonData = [
                'lesson_id' => $lesson->id,
                'lesson_title' => $lesson->title,
                'lesson_description' => $lesson->description,
                'lesson_order' => $lesson->order_number,
                'total_lectures' => $totalLectures,
                'completed_lectures' => $completedLectures,
                'lecture_progress_percent' => $totalLectures > 0 ? round(($completedLectures / $totalLectures) * 100, 2) : 0,
                'quizzes' => []
            ];

            foreach ($quizzes as $quiz) {
                // Get best attempt
                $bestAttempt = DB::table('student_quiz_attempts')
                    ->where('student_number', $studentNumber)
                    ->where('quiz_id', $quiz->quiz_id)
                    ->where('status', 'graded')
                    ->orderBy('score', 'desc')
                    ->first();

                // Get total attempts
                $attemptCount = DB::table('student_quiz_attempts')
                    ->where('student_number', $studentNumber)
                    ->where('quiz_id', $quiz->quiz_id)
                    ->whereIn('status', ['submitted', 'graded'])
                    ->count();

                // Check availability
                $now = now();
                $isAvailable = true;
                $availabilityMessage = 'Available';

                if ($quiz->available_from && $now->lt($quiz->available_from)) {
                    $isAvailable = false;
                    $availabilityMessage = 'Opens ' . \Carbon\Carbon::parse($quiz->available_from)->format('M d, Y g:i A');
                } elseif ($quiz->available_until && $now->gt($quiz->available_until)) {
                    $isAvailable = false;
                    $availabilityMessage = 'Closed';
                }

                $quizData = [
                    'quiz_id' => $quiz->quiz_id,
                    'quiz_title' => $quiz->quiz_title,
                    'quarter_id' => $quiz->quarter_id,
                    'quarter_name' => $quiz->quarter_name,
                    'quarter_code' => $quiz->quarter_code,
                    'passing_score' => (float) $quiz->passing_score,
                    'max_attempts' => $quiz->max_attempts,
                    'attempt_count' => $attemptCount,
                    'available_from' => $quiz->available_from,
                    'available_until' => $quiz->available_until,
                    'is_available' => $isAvailable,
                    'availability_message' => $availabilityMessage,
                    'score' => null,
                    'total_points' => null,
                    'percentage' => null,
                    'submitted_at' => null,
                    'status' => 'not_taken'
                ];

                if ($bestAttempt) {
                    $quizData['score'] = (float) $bestAttempt->score;
                    $quizData['total_points'] = (float) $bestAttempt->total_points;
                    
                    if ($bestAttempt->total_points > 0) {
                        $quizData['percentage'] = round(($bestAttempt->score / $bestAttempt->total_points) * 100, 2);
                    }
                    
                    $quizData['submitted_at'] = $bestAttempt->submitted_at;
                    $quizData['status'] = $quizData['percentage'] >= $quiz->passing_score ? 'passed' : 'failed';
                }

                $lessonData['quizzes'][] = $quizData;
            }

            $gradesData[] = $lessonData;
        }

        // Calculate overall statistics
        $totalQuizzes = 0;
        $completedQuizzes = 0;
        $passedQuizzes = 0;
        $totalScore = 0;
        $totalPossible = 0;

        foreach ($gradesData as $lesson) {
            foreach ($lesson['quizzes'] as $quiz) {
                $totalQuizzes++;
                if ($quiz['status'] !== 'not_taken') {
                    $completedQuizzes++;
                    if ($quiz['status'] === 'passed') {
                        $passedQuizzes++;
                    }
                    if ($quiz['total_points']) {
                        $totalScore += $quiz['score'];
                        $totalPossible += $quiz['total_points'];
                    }
                }
            }
        }

        $overallStats = [
            'total_quizzes' => $totalQuizzes,
            'completed_quizzes' => $completedQuizzes,
            'passed_quizzes' => $passedQuizzes,
            'overall_percentage' => $totalPossible > 0 ? round(($totalScore / $totalPossible) * 100, 2) : 0,
            'completion_rate' => $totalQuizzes > 0 ? round(($completedQuizzes / $totalQuizzes) * 100, 2) : 0,
            'pass_rate' => $completedQuizzes > 0 ? round(($passedQuizzes / $completedQuizzes) * 100, 2) : 0
        ];

        return response()->json([
            'grades' => $gradesData,
            'quarters' => $quarters,
            'overall_stats' => $overallStats,
            'student_number' => $studentNumber,
            'class' => [
                'id' => $class->id,
                'code' => $class->class_code,
                'name' => $class->class_name
            ]
        ]);
    }

    public function getGrades($classId)
{
    try {
        $teacher = Auth::guard('teacher')->user();
        
        // Verify the class exists and belongs to the teacher
        $class = DB::table('classes')
            ->where('id', $classId)
            ->where('teacher_id', $teacher->id)
            ->first();

        if (!$class) {
            return response()->json(['error' => 'Class not found'], 404);
        }

        // Get all quizzes for this class with lesson information
        $quizzes = DB::table('quizzes as q')
            ->join('lessons as l', 'q.lesson_id', '=', 'l.id')
            ->where('l.class_id', $classId)
            ->where('q.status', 1)
            ->select(
                'q.id',
                'q.title',
                'q.total_points',
                'l.title as lesson_title',
                'l.order_number as lesson_order'
            )
            ->orderBy('l.order_number')
            ->orderBy('q.id')
            ->get();

        // Get all enrolled students
        $students = DB::table('students as s')
            ->leftJoin('student_class_matrix as scm', function($join) use ($class) {
                $join->on('s.student_number', '=', 'scm.student_number')
                     ->where('scm.class_code', '=', $class->class_code);
            })
            ->leftJoin('section_class_matrix as secm', function($join) use ($classId) {
                $join->on('s.section_id', '=', 'secm.section_id')
                     ->where('secm.class_id', '=', $classId);
            })
            ->where(function($query) {
                $query->whereNotNull('scm.student_number')
                      ->orWhereNotNull('secm.section_id');
            })
            ->select(
                's.student_number',
                's.first_name',
                's.middle_name',
                's.last_name',
                's.gender'
            )
            ->distinct()
            ->get();

        // Build grades array
        $grades = [];
        
        foreach ($students as $student) {
            $fullName = trim($student->first_name . ' ' . 
                           ($student->middle_name ? $student->middle_name[0] . '. ' : '') . 
                           $student->last_name);
            
            $studentData = [
                'student_number' => $student->student_number,
                'full_name' => $fullName,
                'gender' => $student->gender,
                'quizzes' => []
            ];

            // Get quiz attempts for this student
            foreach ($quizzes as $quiz) {
                $bestAttempt = DB::table('student_quiz_attempts')
                    ->where('student_number', $student->student_number)
                    ->where('quiz_id', $quiz->id)
                    ->where('status', 'graded')
                    ->orderBy('score', 'desc')
                    ->first();

                $studentData['quizzes'][$quiz->id] = [
                    'score' => $bestAttempt ? $bestAttempt->score : null,
                    'total' => $quiz->total_points,
                    'percentage' => $bestAttempt && $quiz->total_points > 0 
                        ? round(($bestAttempt->score / $quiz->total_points) * 100, 2) 
                        : null
                ];
            }

            $grades[] = $studentData;
        }

        return response()->json([
            'grades' => $grades,
            'quizzes' => $quizzes,
            'class' => [
                'id' => $class->id,
                'code' => $class->class_code,
                'name' => $class->class_name
            ]
        ]);

    } catch (\Exception $e) {
        \Log::error('Error fetching grades: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to fetch grades'], 500);
    }
}
public function getTeacherGrades($classId)
{
    try {
        // Verify class exists
        $class = DB::table('classes')->where('id', $classId)->first();
        
        if (!$class) {
            return response()->json(['error' => 'Class not found'], 404);
        }

        // Get all enrolled students (both direct and section-based) with FULL DETAILS
        $directStudents = DB::table('student_class_matrix as scm')
            ->join('students as s', 'scm.student_number', '=', 's.student_number')
            ->where('scm.class_code', $class->class_code)
            ->select(
                's.student_number', 
                's.first_name', 
                's.middle_name', 
                's.last_name', 
                's.gender'
            )
            ->get();

        $sectionStudents = DB::table('section_class_matrix as scm')
            ->join('sections as sec', 'scm.section_id', '=', 'sec.id')
            ->join('students as s', 's.section_id', '=', 'sec.id')
            ->where('scm.class_id', $classId)
            ->select(
                's.student_number', 
                's.first_name', 
                's.middle_name', 
                's.last_name', 
                's.gender'
            )
            ->get();

        // Merge and remove duplicates
        $allStudents = $directStudents->merge($sectionStudents)
            ->unique('student_number')
            ->sortBy([
                ['gender', 'asc'],      // Male first, then Female
                ['last_name', 'asc'],   // Then by last name ascending
                ['first_name', 'asc']   // Then by first name ascending
            ]);

        // Get all quizzes for this class
        $quizzes = DB::table('quizzes as q')
            ->join('lessons as l', 'q.lesson_id', '=', 'l.id')
            ->where('l.class_id', $classId)
            ->where('q.status', 1)
            ->where('l.status', 1)
            ->select(
                'q.id',
                'q.title',
                'q.lesson_id',
                'l.title as lesson_title',
                'l.order_number as lesson_order'
            )
            ->orderBy('l.order_number')
            ->orderBy('q.id')
            ->get();

        // Build grades array
        $grades = [];
        
        foreach ($allStudents as $student) {
            // Format name properly: Last Name, First Name Middle Name
            $fullName = $student->last_name . ', ' . $student->first_name;
            if (!empty($student->middle_name)) {
                $fullName .= ' ' . $student->middle_name;
            }
            
            $studentGrades = [
                'student_number' => $student->student_number,
                'full_name' => $fullName,
                'gender' => strtolower($student->gender ?? 'unknown'),
                'quizzes' => []
            ];

            foreach ($quizzes as $quiz) {
                // Get best attempt for this quiz
                $bestAttempt = DB::table('student_quiz_attempts')
                    ->where('student_number', $student->student_number)
                    ->where('quiz_id', $quiz->id)
                    ->where('status', 'graded')
                    ->orderBy('score', 'desc')
                    ->first();

                $studentGrades['quizzes'][$quiz->id] = [
                    'score' => $bestAttempt ? $bestAttempt->score : null,
                    'total' => $bestAttempt ? $bestAttempt->total_points : 0,
                    'percentage' => $bestAttempt && $bestAttempt->total_points > 0 
                        ? round(($bestAttempt->score / $bestAttempt->total_points) * 100, 2) 
                        : 0
                ];
            }

            $grades[] = $studentGrades;
        }

        return response()->json([
            'grades' => $grades,
            'quizzes' => $quizzes,
            'class' => [
                'id' => $class->id,
                'code' => $class->class_code,
                'name' => $class->class_name
            ]
        ]);

    } catch (\Exception $e) {
        \Log::error('Error fetching teacher grades: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to load grades'], 500);
    }
}
}