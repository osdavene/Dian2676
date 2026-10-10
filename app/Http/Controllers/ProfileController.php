<?php

namespace App\Http\Controllers;

use App\Models\JobProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        $jobProfiles = JobProfile::where('is_active', true)->orderBy('id')->get();
        return view('profile.show', compact('user', 'jobProfiles'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'current_password' => ['nullable', 'required_with:new_password', 'current_password'],
            'new_password' => ['nullable', 'confirmed', Password::min(6)],
        ];

        // Solo un administrador tiene permiso para modificar el cargo/nivel asignado
        if ($user->isAdmin()) {
            $rules['job_profile_id'] = ['nullable', 'exists:job_profiles,id'];
        }

        $request->validate($rules);

        $user->name = $request->name;
        $user->email = $request->email;

        if ($user->isAdmin() && $request->filled('job_profile_id')) {
            $job = JobProfile::findOrFail($request->job_profile_id);
            $user->job_profile_id = $job->id;
            $user->target_cargo = $job->name;
        }

        if ($request->filled('new_password')) {
            $user->password = Hash::make($request->new_password);
        }

        $user->save();

        return back()->with('success', 'Tu información de perfil ha sido actualizada exitosamente.');
    }
}
