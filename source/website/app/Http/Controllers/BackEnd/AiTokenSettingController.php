<?php

namespace App\Http\Controllers\BackEnd;

use App\Http\Controllers\Controller;
use App\Models\BasicSettings\Basic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class AiTokenSettingController extends Controller
{
    public function index()
    {
        $data = Basic::where('uniqid', 12345)->firstOrFail();

        return view('backend.ai-token-settings.index', compact('data'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'ai_system_status' => 'required|in:0,1',
        ]);

        Basic::where('uniqid', 12345)->update([
            'ai_system_status' => (int) $request->ai_system_status,
        ]);

        Session::flash('success', 'AI setting updated successfully!');

        return redirect()->route('admin.ai_token_settings.index');
    }
}

