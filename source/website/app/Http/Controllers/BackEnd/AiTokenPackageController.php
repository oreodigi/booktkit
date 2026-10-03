<?php

namespace App\Http\Controllers\BackEnd;

use App\Http\Controllers\Controller;
use App\Models\AiTokenPackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class AiTokenPackageController extends Controller
{
  public function index()
  {
    $packages = AiTokenPackage::orderBy('id', 'desc')->get();

    return view('backend.ai-token-packages.index', compact('packages'));
  }

  public function create()
  {
    return view('backend.ai-token-packages.create');
  }

  public function store(Request $request)
  {
    $request->validate([
      'title' => 'required|string|max:255',
      'ai_engine' => 'required|string|max:50',
      'ai_token_limit' => 'required|integer|min:0',
      'ai_image_limit' => 'required|integer|min:0',
      'status' => 'required|in:0,1',
      'price' => 'required|numeric|min:0',
    ]);

    AiTokenPackage::create([
      'title' => $request->title,
      'ai_engine' => $request->ai_engine,
      'ai_token_limit' => $request->ai_token_limit,
      'ai_image_limit' => $request->ai_image_limit,
      'status' => $request->status,
      'price' => $request->price,
    ]);

    Session::flash('success', 'Added Successfully');

    return redirect()->route('admin.ai_token_packages.index');
  }

  public function edit($id)
  {
    $package = AiTokenPackage::findOrFail($id);

    return view('backend.ai-token-packages.edit', compact('package'));
  }

  public function update(Request $request, $id)
  {
    $request->validate([
      'title' => 'required|string|max:255',
      'ai_engine' => 'required|string|max:50',
      'ai_token_limit' => 'required|integer|min:0',
      'ai_image_limit' => 'required|integer|min:0',
      'status' => 'required|in:0,1',
      'price' => 'required|numeric|min:0',
    ]);

    $package = AiTokenPackage::findOrFail($id);

    $package->update([
      'title' => $request->title,
      'ai_engine' => $request->ai_engine,
      'ai_token_limit' => $request->ai_token_limit,
      'ai_image_limit' => $request->ai_image_limit,
      'status' => $request->status,
      'price' => $request->price,
    ]);

    Session::flash('success', 'Updated Successfully');

    return redirect()->route('admin.ai_token_packages.index');
  }

  public function destroy($id)
  {
    $package = AiTokenPackage::findOrFail($id);
    $package->delete();

    return redirect()->back()->with('success', 'Deleted Successfully');
  }
}
