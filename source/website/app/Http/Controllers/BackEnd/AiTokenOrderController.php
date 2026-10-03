<?php

namespace App\Http\Controllers\BackEnd;

use App\Http\Controllers\Controller;
use App\Models\OrganizerTokenPurchase;
use App\Services\OrganizerAiTokenPurchaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class AiTokenOrderController extends Controller
{
  protected OrganizerAiTokenPurchaseService $service;

  public function __construct(OrganizerAiTokenPurchaseService $service)
  {
    $this->service = $service;
  }

  public function index(Request $request)
  {
    $query = OrganizerTokenPurchase::query()->orderBy('id', 'desc');

    if ($request->filled('status')) {
      $query->where('status', $request->status);
    }

    if ($request->filled('payment_status')) {
      $query->where('payment_status', $request->payment_status);
    }

    $orders = $query->paginate(10);

    return view('backend.ai-token-orders.index', compact('orders'));
  }

  public function show($id)
  {
    $order = OrganizerTokenPurchase::findOrFail($id);

    return view('backend.ai-token-orders.show', compact('order'));
  }

  public function updateStatus(Request $request, $id)
  {
    $request->validate([
      'status' => 'required|in:pending,approved,rejected',
    ]);

    $order = OrganizerTokenPurchase::findOrFail($id);

    if ($order->status === 'approved' && $request->status !== 'approved') {
      Session::flash('warning', 'Approved orders cannot be changed.');

      return redirect()->route('admin.ai_token_orders.index');
    }

    $previousStatus = $order->status;

    DB::transaction(function () use ($order, $request, $previousStatus) {
      $order->update([
        'status' => $request->status,
      ]);

      if ($previousStatus !== 'approved' && $request->status === 'approved') {
        $this->service->syncBalanceOnApproval($order->fresh());
      }
    });

    if ($previousStatus !== 'approved' && $request->status === 'approved') {
      $this->service->sendApprovalMail($order->fresh());
    }

    Session::flash('success', 'Status updated successfully!');

    return redirect()->route('admin.ai_token_orders.index');
  }

  public function updatePaymentStatus(Request $request, $id)
  {
    $request->validate([
      'payment_status' => 'required|in:pending,paid,failed',
    ]);

    $order = OrganizerTokenPurchase::findOrFail($id);

    if ($order->payment_method !== 'offline') {
      Session::flash('warning', 'Only offline orders can be updated manually.');

      return redirect()->route('admin.ai_token_orders.index');
    }

    if ($order->status === 'approved' && $request->payment_status !== 'paid') {
      Session::flash('warning', 'Approved orders must remain paid.');

      return redirect()->route('admin.ai_token_orders.index');
    }

    $order->update([
      'payment_status' => $request->payment_status,
    ]);

    Session::flash('success', 'Payment status updated successfully!');

    return redirect()->route('admin.ai_token_orders.index');
  }
}
