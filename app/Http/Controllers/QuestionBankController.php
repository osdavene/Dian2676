<?php

namespace App\Http\Controllers;

use App\Models\JobProfile;
use App\Models\ThematicAxis;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuestionBankController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $jobId = $request->input('job_id', $user->job_profile_id ?? 1);
        $axisId = $request->input('axis_id');
        $search = $request->input('search');
        $difficulty = $request->input('difficulty');

        $jobs = JobProfile::where('is_active', true)->orderBy('id')->get();

        if ($jobId === 'todos') {
            $axes = ThematicAxis::orderBy('name')->get();
            $query = Question::where('is_active', true)->with(['thematicAxis', 'jobProfile']);
        } else {
            $axes = ThematicAxis::where('job_profile_id', $jobId)->orderBy('name')->get();
            $query = Question::forCargo($jobId)
                ->where('is_active', true)
                ->with(['thematicAxis', 'jobProfile']);
        }

        if ($axisId) {
            $query->where('thematic_axis_id', $axisId);
        }

        if ($difficulty) {
            $query->where('difficulty', $difficulty);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('stem', 'LIKE', "%{$search}%")
                  ->orWhere('case_text', 'LIKE', "%{$search}%")
                  ->orWhere('legal_basis', 'LIKE', "%{$search}%");
            });
        }

        $questions = $query->orderBy('id')->paginate(15)->withQueryString();

        return view('question_bank.index', compact('questions', 'jobs', 'axes', 'jobId', 'axisId', 'search', 'difficulty', 'user'));
    }
}
