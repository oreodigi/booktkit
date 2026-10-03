<?php

namespace App\Http\Controllers\Api\Organizer;

use App\Http\Controllers\Api\HelperController;
use Carbon\Carbon;
use App\Models\Conversation;
use Illuminate\Http\Request;
use App\Models\SupportTicket;
use App\Models\SupportTicketStatus;
use Mews\Purifier\Facades\Purifier;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SupportTicketController extends Controller
{
  //index
  public function index(Request $request)
  {
    $s_status = SupportTicketStatus::first();
    if ($s_status->support_ticket_status != 'active') {
      return response()->json([
        'status' => false,
        'message' => 'Support ticket service is currently unavailable. Please try again later.'
      ], 403);
    }

    $collection = SupportTicket::where([['user_id', Auth::guard('organizer_sanctum')->user()->id], ['user_type', 'organizer']])
      ->orderByDesc('id')
      ->paginate(10);
    $collection->transform(function ($col) {
      $col->attachment = HelperController::getImagePath('assets/admin/img/support-ticket/attachment/', $col->attachment);
      return $col;
    });

    return response()->json([
      'success' => true,
      'data' => $collection,
    ]);
  }
  //store
  public function store(Request $request)
  {
    $s_status = SupportTicketStatus::first();
    if ($s_status->support_ticket_status != 'active') {
      return response()->json([
        'status' => false,
        'message' => 'Support ticket service is currently unavailable. Please try again later.'
      ], 403);
    }

    $rules = [
      'email' => 'required',
      'subject' => 'required',
    ];

    $file = $request->file('attachment');
    $allowedExts = array('zip');
    $rules['attachment'] = [
      function ($attribute, $value, $fail) use ($file, $allowedExts) {
        $ext = $file->getClientOriginalExtension();
        if (!in_array($ext, $allowedExts)) {
          return $fail("Only zip file supported");
        }
      },
      'max:5120'
    ];

    $messages = [
      'attachment.max' => __('Attachment may not be greater than 5 MB'),
    ];
    $validator = Validator::make($request->all(), $rules, $messages);
    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'errors' => $validator->errors(),
      ], 422);
    }

    $in = $request->all();
    if ($request->hasFile('attachment')) {
      $attachment = $request->file('attachment');
      $filename = uniqid() . '.' . $attachment->getClientOriginalExtension();
      $attachment->move(public_path('assets/admin/img/support-ticket/attachment/'), $filename);
      $in['attachment'] = $filename;
    }
    $in['user_id'] = Auth::guard('organizer_sanctum')->user()->id;
    $in['user_type'] = 'organizer';
    $in['description'] = Purifier::clean($request->description, 'youtube');
    SupportTicket::create($in);

    return response()->json([
      'success' => true,
      'message' => __('Support Ticket Created Successfully!'),
    ]);
  }
  //message
  public function message($id)
  {
    $s_status = SupportTicketStatus::first();
    if ($s_status->support_ticket_status != 'active') {
      return response()->json([
        'status' => false,
        'message' => 'Support ticket service is currently unavailable. Please try again later.'
      ], 403);
    }
    $organizer_id = Auth::guard('organizer_sanctum')->user()->id;

    $ticket = SupportTicket::where([
      ['id', $id],
      ['user_id', $organizer_id],
    ])->first();

    if (!$ticket) {
      return response()->json([
        'status' => false,
        'message' => 'Ticket is not Found'
      ], 404);
    }

    $ticket->messages->transform(function ($message) {
      $message->file = HelperController::getImagePath(
        'assets/admin/img/support-ticket/',
        $message->file
      );
      return $message;
    });
    $queryResult['ticket'] = $ticket;

    return response()->json([
      'success' => true,
      'data' => $queryResult,
    ]);
  }


  public function ticketreply(Request $request, $id)
  {
    $s_status = SupportTicketStatus::first();
    if ($s_status->support_ticket_status != 'active') {
      return response()->json([
        'status' => false,
        'message' => 'Support ticket service is currently unavailable. Please try again later.'
      ], 403);
    }
    $organizer_id = Auth::guard('organizer_sanctum')->user()->id;

    $ticket = SupportTicket::where([
      ['id', $id],
      ['user_id', $organizer_id],
    ])->first();

    if (!$ticket) {
      return response()->json([
        'status' => false,
        'message' => 'Ticket is not Found'
      ], 404);
    }
    if ($ticket->status == 1) {
      return response()->json([
        'status' => false,
        'message' => 'Ticket is pending'
      ], 409);
    }
    if ($ticket->status == 3) {
      return response()->json([
        'status' => false,
        'message' => 'Ticket is already closed'
      ], 422);
    }

    $file = $request->file('file');
    $allowedExts = array('zip');
    $rules = [
      'reply' => 'required',
      'file' => [
        function ($attribute, $value, $fail) use ($file, $allowedExts) {

          $ext = $file->getClientOriginalExtension();
          if (!in_array($ext, $allowedExts)) {
            return $fail("Only zip file supported");
          }
        },
        'max:5120'
      ],
    ];

    $messages = [
      'file.max' => ' Zip file may not be greater than 5 MB',
    ];

    $request->validate($rules, $messages);
    $input = $request->all();

    $reply = str_replace(url('/') . '/assets/front/img/', "{base_url}/assets/front/img/", $request->reply);
    $input['reply'] = Purifier::clean($reply, 'youtube');
    $input['user_id'] = Auth::guard('organizer_sanctum')->user()->id;
    $input['type'] = 3;

    $input['support_ticket_id'] = $id;
    if ($request->hasFile('file')) {
      $file = $request->file('file');
      $filename = uniqid() . '.' . $file->getClientOriginalExtension();
      $file->move(public_path('assets/admin/img/support-ticket/'), $filename);
      $input['file'] = $filename;
    }

    $data = new Conversation();
    $data->create($input);

    $files = glob('assets/front/temp/*');
    foreach ($files as $file) {
      unlink($file);
    }

    SupportTicket::where('id', $id)->update([
      'last_message' => Carbon::now(),
    ]);
    return response()->json([
      'success' => true,
      'message' => __('Reply Sent Successfully!'),
    ]);
  }

  //delete
  public function delete($id)
  {
    $s_status = SupportTicketStatus::first();
    if ($s_status->support_ticket_status != 'active') {
      return response()->json([
        'status' => false,
        'message' => 'Support ticket service is currently unavailable. Please try again later.'
      ], 403);
    }

    //delete all support ticket
    $organizer_id = Auth::guard('organizer_sanctum')->user()->id;

    $ticket = SupportTicket::where([
      ['id', $id],
      ['user_id', $organizer_id],
    ])->first();

    if (!$ticket) {
      return response()->json([
        'status' => false,
        'message' => 'Ticket is not Found'
      ], 404);
    }
    if ($ticket) {
      //delete conversation
      $messages = $ticket->messages()->get();
      foreach ($messages as $message) {
        @unlink(public_path('assets/admin/img/support-ticket/' . $message->file));
        $message->delete();
      }
      @unlink(public_path('assets/admin/img/support-ticket/attachment/') . $ticket->attachment);
      $ticket->delete();
    }

    return response()->json([
      'success' => true,
      'message' => __('Support Ticket Deleted Successfully!'),
    ]);
  }
}
