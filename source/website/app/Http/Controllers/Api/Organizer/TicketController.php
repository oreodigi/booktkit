<?php

namespace App\Http\Controllers\Api\Organizer;

use App\Http\Controllers\Api\HelperController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Language;
use App\Models\Event;
use App\Models\Event\EventContent;
use App\Models\Event\Ticket;
use App\Models\Event\TicketVariation;
use App\Http\Requests\Event\TicketRequest;
use App\Models\Event\Slot;
use App\Models\Event\SlotImage;
use App\Models\Event\TicketContent;
use App\Models\Event\VariationContent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class TicketController extends Controller
{
  public function index(Request $request)
  {
    $organizer_id = Auth::guard('organizer_sanctum')->user()->id;
    $event = Event::where([['id', $request->event_id], ['organizer_id', $organizer_id]])->first();
    if (!$event) {
      return response()->json([
        'success' => false,
        'message' => "You are not authorized to access this event."
      ]);
    }
    $information['langs'] = Language::all();

    //get language
    $language = HelperController::getAppLanguage($request);
    $information['language'] = $language;


    $event = EventContent::where('event_id', $request->event_id)->where('language_id', $language->id)->first();
    if (empty($event)) {
      $event = EventContent::where('event_id', $request->event_id)->first();
    }
    $tickets = Ticket::join('ticket_contents', 'ticket_contents.ticket_id', '=', 'tickets.id')
      ->where('tickets.event_id', $request->event_id)
      ->where('ticket_contents.language_id', $language->id)
      ->select('tickets.*', 'ticket_contents.title as title')
      ->orderBy('tickets.id', 'desc')
      ->get();
    $information['getCurrencyInfo']  = $this->getCurrencyInfo();

    $information['event'] = $event;

    $information['tickets'] = $tickets;

    return response()->json([
      'success' => true,
      'data' => $information
    ]);
  }
  //create
  public function create(Request $request)
  {
    $organizer_id = Auth::guard('organizer_sanctum')->user()->id;
    $event = Event::where([['id', $request->event_id], ['organizer_id', $organizer_id]])->first();
    if (!$event) {
      return response()->json([
        'success' => false,
        'message' => "You are not authorized to access this event."
      ]);
    }

    $information['langs'] = Language::all();

    //get language
    $language = HelperController::getAppLanguage($request);
    $information['language'] = $language;

    $event = EventContent::where('event_id', $request->event_id)->where('language_id', $language->id)->first();
    if (empty($event)) {
      $event = EventContent::where('event_id', $request->event_id)->first();
    }

    $eventType = Event::where('id', $request->event_id)->select('event_type')->first();
    $information['eventType'] = $eventType;
    $information['event'] = $event;
    $information['getCurrencyInfo']  = $this->getCurrencyInfo();

    return response()->json([
      'success' => true,
      'data' => $information
    ]);
  }
  //store
  public function store(Request $request)
  {
    Log::info($request->all());

    $rules = [
      'pricing_type_2'             => 'required',
      'ticket_available_type'      => 'required',
      'max_ticket_buy_type'        => 'required',
      'early_bird_discount_type'   => 'required',


      'ticket_available'           => 'required_if:ticket_available_type,limited',
      'max_buy_ticket'             => 'required_if:max_ticket_buy_type,limited',
      'discount_type'              => 'required_if:early_bird_discount_type,enable',
      'early_bird_discount_amount' => 'required_if:early_bird_discount_type,enable',
      'early_bird_discount_date'   => 'required_if:early_bird_discount_type,enable',
      'early_bird_discount_time'   => 'required_if:early_bird_discount_type,enable',
    ];

    /** pricing_type = normal */
    if ($request->pricing_type_2 === 'normal') {
      $rules['price'] = 'required|numeric|min:0';

      if ($request->early_bird_discount_type === 'enable') {
        if ($request->discount_type === 'percentage') {
          $rules['early_bird_discount_amount'] = 'numeric|between:1,99';
        } elseif ($request->discount_type === 'fixed') {
          $price = $request->price - 1;
          $rules['early_bird_discount_amount'] = "numeric|between:1,{$price}";
        }
      }
    }

    /** pricing_type = variation */
    if ($request->pricing_type_2 === 'variation') {
      $rules['variation_name.*']  = 'required';
      $rules['variation_price.*'] = 'required|numeric|min:1';

      if ($request->early_bird_discount_type === 'enable') {
        if ($request->discount_type === 'percentage') {
          $rules['early_bird_discount_amount'] = 'numeric|between:1,99';
        } elseif ($request->discount_type === 'fixed') {
          $price = min($request->variation_price) - 1;
          $rules['early_bird_discount_amount'] = "numeric|between:1,{$price}";
        }
      }
    }

    /** Language wise title validation */
    $languages = Language::all();
    foreach ($languages as $language) {
      $rules[$language->code . '_title'] = 'required';
    }

    /** Custom messages */
    $messages = [
      'variation_name.*.required'  => 'The variation name field is required.',
      'variation_price.*.required' => 'The variation price field is required.',
      'variation_price.*.min'      => 'The variation price must be at least 1.',
      'v_ticket_available.*.required' => 'The ticket available field is required.',
      'v_ticket_available.*.min'      => 'The ticket available field must be at least 1.',
    ];

    foreach ($languages as $language) {
      $messages[$language->code . '_title.required']
        = 'The Ticket name field is required for ' . $language->name;
    }

    $validator = Validator::make($request->all(), $rules,  $messages);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'errors'  => $validator->errors()->toArray()
      ], 400);
    }

    $organizer = Auth::guard('organizer_sanctum')->user();

    $event = Event::where('id', $request->event_id)
      ->where('organizer_id', $organizer->id)
      ->first();

    if (! $event) {
      return response()->json([
        'success' => false,
        'message' => 'You are not authorized to this event.'
      ], 403);
    }

    $in = $request->all();
    $in['early_bird_discount'] = $request->early_bird_discount_type;
    $in['early_bird_discount_type'] = $request->discount_type;
    if ($request->pricing_type_2 == 'free') {
      $in['pricing_type'] = 'free';
      $in['price'] = 0;
      $in['free_tickete_slot_enable'] = 0;
      $in['free_tickete_slot_unique_id'] = rand(000000, 999999);
      $in['slot_seat_min_price'] = 0;
      $ticket =  Ticket::create($in);
    } elseif ($request->pricing_type_2 == 'normal') {
      $in['pricing_type'] = 'normal';
      $in['price'] = $request->price;
      $in['f_price'] = $request->price;
      $in['normal_ticket_slot_enable'] = 0;
      $in['normal_ticket_slot_unique_id'] = rand(000000, 999999);
      $in['slot_seat_min_price'] = 0;
      $ticket =  Ticket::create($in);
    } elseif ($request->pricing_type_2 == 'variation') {
      $in['pricing_type'] = 'variation';
      $f_price = max($request->variation_price);
      $in['f_price'] = $f_price;
      $variations = [];
      $languages = Language::get();
      foreach ($languages as $language) {
        if ($language->is_default == 1) {
          $variation_datas = $request[$language->code . '_variation_name'];
          if (!empty($variation_datas)) {
            foreach ($variation_datas as $key => $varName) {
              $variations[] = [
                'name' => $varName,
                'price' => $request->variation_price[$key],
                'ticket_available_type' => $request->v_ticket_available_type[$key],
                'ticket_available' => $request->v_ticket_available[$key],
                'max_ticket_buy_type' => $request->v_max_ticket_buy_type[$key],
                'v_max_ticket_buy' => $request->v_max_ticket_buy[$key],
                'slot_enable' => 0,
                'slot_unique_id' => rand(000000, 999999),
                'slot_seat_min_price' => 0,
              ];
            }
          }
        }
      }

      $variations = json_encode($variations);
      $in['variations'] = $variations;
      $ticket = Ticket::create($in);

      if ($request->pricing_type_2 == 'variation') {
        $languages = Language::get();
        foreach ($languages as $language) {
          $variation_datas = $request[$language->code . '_variation_name'];
          foreach ($variation_datas as $key => $data) {
            $variations_data['name'] = $data;
            $variations_data['key'] = $key;
            $variations_data['language_id'] = $language->id;
            $variations_data['ticket_id'] = $ticket->id;
            VariationContent::create($variations_data);
          }
        }
      }
    }

    $languages = Language::all();
    foreach ($languages as $language) {
      $data = [];
      $data['language_id'] = $language->id;
      $data['ticket_id'] = $ticket->id;
      $data['title'] = $request[$language->code . '_title'];
      $data['description'] = $request[$language->code . '_description'];
      TicketContent::create($data);
    }

    return response()->json([
      'success' => true,
      'message' => __('Ticket added successfully!')
    ]);
  }
  //edit
  public function edit(Request $request)
  {
    $organizer_id = Auth::guard('organizer_sanctum')->user()->id;
    $event = Event::where('organizer_id', $organizer_id)
      ->where('id', $request->event_id)
      ->first();


    if (!$event) {
      return response()->json([
        'success' => false,
        'message' => 'You are not authorized to see this event.'
      ], 403);
    }

    $ticket = Ticket::where('id', $request->id)->Where('event_id', $request->event_id)->first();
    if (!$ticket) {
      return response()->json([
        'success' => false,
        'message' => 'Ticket is not found'
      ], 403);
    }
    $this->includeSlotSystemVariable($ticket->id);
    $languages = Language::get();
    $information['languages'] = $languages;
    //get language
    $language = HelperController::getAppLanguage($request);
    $information['language'] = $language;
    $ticket = Ticket::where('id', $request->id)->firstOrFail();
    $information['ticket'] = $ticket;
    $variations = json_decode($ticket->variations, true);

    if (!empty($variations)) {

      foreach ($variations as $key => &$variation) { // 🔴 & very important

        foreach ($languages as $language) {

          $content = VariationContent::where([
            ['ticket_id', $ticket->id],
            ['key', $key],
            ['language_id', $language->id],
          ])->first();

          // en_name / ar_name / etc
          $variation[$language->code . '_name'] = $content->name ?? null;
        }

        // old single name remove
        unset($variation['name']);
      }

      unset($variation); // 🔒 safety (recommended)
    }

    $information['variations'] = $variations;

    $information['getCurrencyInfo']  = $this->getCurrencyInfo();
    $information['event_id'] = $request->event_id;
    $information['ticket_contents'] = TicketContent::where('ticket_id', $ticket->id)->get();
    $information['ticket_id'] = $ticket->id;
    return response()->json([
      'success' => true,
      'data' => $information
    ]);
  }
  //update
  public function update(Request $request)
  {
    Log::info($request->all());

    $rules = [
      'pricing_type_2'             => 'required',
      'ticket_available_type'      => 'required',
      'max_ticket_buy_type'        => 'required',
      'early_bird_discount_type'   => 'required',
      'ticket_available'           => 'required_if:ticket_available_type,limited',
      'max_buy_ticket'             => 'required_if:max_ticket_buy_type,limited',
      'discount_type'              => 'required_if:early_bird_discount_type,enable',
      'early_bird_discount_amount' => 'required_if:early_bird_discount_type,enable',
      'early_bird_discount_date'   => 'required_if:early_bird_discount_type,enable',
      'early_bird_discount_time'   => 'required_if:early_bird_discount_type,enable',
    ];

    /** pricing_type = normal */
    if ($request->pricing_type_2 === 'normal') {
      $rules['price'] = 'required|numeric|min:0';

      if ($request->early_bird_discount_type === 'enable') {
        if ($request->discount_type === 'percentage') {
          $rules['early_bird_discount_amount'] = 'numeric|between:1,99';
        } elseif ($request->discount_type === 'fixed') {
          $price = $request->price - 1;
          $rules['early_bird_discount_amount'] = "numeric|between:1,{$price}";
        }
      }
    }

    /** pricing_type = variation */
    if ($request->pricing_type_2 === 'variation') {
      $rules['variation_name.*']  = 'required';
      $rules['variation_price.*'] = 'required|numeric|min:1';

      if ($request->early_bird_discount_type === 'enable') {
        if ($request->discount_type === 'percentage') {
          $rules['early_bird_discount_amount'] = 'numeric|between:1,99';
        } elseif ($request->discount_type === 'fixed') {
          $price = min($request->variation_price) - 1;
          $rules['early_bird_discount_amount'] = "numeric|between:1,{$price}";
        }
      }
    }

    /** Language wise title validation */
    $languages = Language::all();
    foreach ($languages as $language) {
      $rules[$language->code . '_title'] = 'required';
    }

    /** Custom messages */
    $messages = [
      'variation_name.*.required'  => 'The variation name field is required.',
      'variation_price.*.required' => 'The variation price field is required.',
      'variation_price.*.min'      => 'The variation price must be at least 1.',
      'v_ticket_available.*.required' => 'The ticket available field is required.',
      'v_ticket_available.*.min'      => 'The ticket available field must be at least 1.',
    ];

    foreach ($languages as $language) {
      $messages[$language->code . '_title.required']
        = 'The Ticket name field is required for ' . $language->name;
    }

    $validator = Validator::make($request->all(), $rules,  $messages);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'errors'  => $validator->errors()->toArray()
      ], 400);
    }

    $organizer = Auth::guard('organizer_sanctum')->user();

    $event = Event::where('id', $request->event_id)
      ->where('organizer_id', $organizer->id)
      ->first();

    if (! $event) {
      return response()->json([
        'success' => false,
        'message' => 'You are not authorized to this event.'
      ], 403);
    }
    $ticket = Ticket::where('id', $request->ticket_id)->Where('event_id', $request->event_id)->first();
    if (!$ticket) {
      return response()->json([
        'success' => false,
        'message' => 'Ticket is not found'
      ], 403);
    }

    $in = $request->except(['slot_seat_min_price']);

    $in['early_bird_discount'] = $request->early_bird_discount_type;
    $in['early_bird_discount_type'] = $request->discount_type;
    $in['ticket_available'] = $request->ticket_available_type == 'limited' ? $request->ticket_available : null;

    $in['max_buy_ticket'] = $request->max_ticket_buy_type == 'limited' ? $request->max_buy_ticket : null;

    if ($request->pricing_type_2 == 'free') {
      $in['pricing_type'] = 'free';
      $in['price'] = 0;
      $in['free_tickete_slot_enable'] = $request->free_tickete_slot_enable;
      $this->updateSlotIsEnable((int)$request->free_tickete_slot_unique_id, $request->free_tickete_slot_enable == "1" ? 1 : 0);
      $ticket =  Ticket::where('id', $request->ticket_id)->first();
      $ticket->update($in);
    } elseif ($request->pricing_type_2 == 'normal') {
      $in['pricing_type'] = 'normal';
      $in['price'] = $request->price;
      $in['f_price'] = $request->price;
      $in['normal_ticket_slot_enable'] = $request->slot_enable_no_vaidation;
      $this->updateSlotIsEnable((int)$request->slot_unique_id_no_vaidation, $request->slot_enable_no_vaidation == "1" ? 1 : 0);
      $ticket =  Ticket::where('id', $request->ticket_id)->first();
      $ticket->update($in);
    } elseif ($request->pricing_type_2 == 'variation') {
      $in['pricing_type'] = 'variation';
      $ticket =  Ticket::where('id', $request->ticket_id)->first();

      $this->deleteRemovedSlotIds($ticket->variations, $request->slot_unique_id_input);

      $languages = Language::get();
      $variations = [];
      foreach ($languages as $language) {
        if ($language->is_default == 1) {
          $variation_datas = $request[$language->code . '_variation_name'];
          if (!empty($variation_datas)) {
            foreach ($variation_datas as $key => $varName) {
              $variations[] = [
                'name' => $varName,
                'price' => $request->variation_price[$key],
                'ticket_available_type' => $request->v_ticket_available_type[$key],
                'ticket_available' => $request->v_ticket_available[$key],
                'max_ticket_buy_type' => $request->v_max_ticket_buy_type[$key],
                'v_max_ticket_buy' => $request->v_max_ticket_buy[$key],
                'slot_enable' => $request->slot_enable_input[$key] == "1" ? 1 : 0,
                'slot_unique_id' => (int)$request->slot_unique_id_input[$key],
                'slot_seat_min_price' => $request->slot_seat_min_price[$key],
              ];
              $this->updateSlotIsEnable((int)$request->slot_unique_id_input[$key], $request->slot_enable_input[$key] == "1" ? 1 : 0);
            }
          }
        }
      }


      $variations = json_encode($variations);
      $in['variations'] = $variations;

      $ticket->update($in);

      $languages = Language::get();
      foreach ($languages as $language) {
        $variation_datas = $request[$language->code . '_variation_name'];
        $variation_contents = VariationContent::where([['language_id', $language->id], ['ticket_id', $ticket->id]])->get();
        foreach ($variation_contents as $key => $variation_content) {
          $variation_content->delete();
        }

        foreach ($variation_datas as $key => $data) {
          $variations_data['name'] = $data;
          $variations_data['key'] = $key;
          $variations_data['language_id'] = $language->id;
          $variations_data['ticket_id'] = $ticket->id;
          VariationContent::create($variations_data);
        }
      }
    }

    $languages = Language::all();
    foreach ($languages as $language) {
      $ticket_content = TicketContent::where([['language_id', $language->id], ['ticket_id', $ticket->id]])->first();
      if (empty($ticket_content)) {
        $ticket_content = new TicketContent();
        $ticket_content->language_id = $language->id;
        $ticket_content->ticket_id = $ticket->id;
      }
      $ticket_content->title = $request[$language->code . '_title'];
      $ticket_content->description = $request[$language->code . '_description'];
      $ticket_content->save();
    }

    return response()->json([
      'success' => true,
      'message' => __('Ticket updated successfully!')
    ]);
  }
  //destroy
  public function destroy(Request $request)
  {


    $ticket = Ticket::where('id', $request->id)->first();
    if (!$ticket) {
      return response()->json([
        'success' => false,
        'message' => 'Ticket is not found'
      ], 403);
    }

    $organizer = Auth::guard('organizer_sanctum')->user();

    $event = Event::where('id', $ticket->event_id)
      ->where('organizer_id', $organizer->id)
      ->first();

    if (! $event) {
      return response()->json([
        'success' => false,
        'message' => 'You are not authorized to this event.'
      ], 403);
    }




    $ticket = Ticket::where('id', $request->id)->first();
    $ticket_contents = TicketContent::where('ticket_id', $ticket->id)->get();
    $variation_contents = VariationContent::where('ticket_id', $ticket->id)->get();
    if (count($ticket_contents) > 0) {
      foreach ($ticket_contents as $ticket_content) {
        $ticket_content->delete();
      }
    }
    if (count($variation_contents) > 0) {
      foreach ($variation_contents as $variation_content) {
        $variation_content->delete();
      }
    }
    $ticket->delete();

    return response()->json([
      'success' => true,
      'message' => __('Ticket deleted successfully!')
    ]);
  }

  //bulk_delete
  public function bulk_delete(Request $request)
  {
    $ids = $request->ids;

    foreach ($ids as $id) {
      $ticket = Ticket::where('id', $id)->first();
      if (!$ticket) {
        return response()->json([
          'success' => false,
          'message' => 'Ticket is not found'
        ], 403);
      }

      $organizer = Auth::guard('organizer_sanctum')->user();

      $event = Event::where('id', $ticket->event_id)
        ->where('organizer_id', $organizer->id)
        ->first();

      if (! $event) {
        return response()->json([
          'success' => false,
          'message' => 'You are not authorized to this event.'
        ], 403);
      }

      $ticket_contents = TicketContent::where('ticket_id', $ticket->id)->get();
      $variation_contents = VariationContent::where('ticket_id', $ticket->id)->get();
      if (count($ticket_contents) > 0) {
        foreach ($ticket_contents as $ticket_content) {
          $ticket_content->delete();
        }
      }
      if (count($variation_contents) > 0) {
        foreach ($variation_contents as $variation_content) {
          $variation_content->delete();
        }
      }
      $ticket->delete();
    }
    return response()->json([
      'success' => true,
      'message' => __('Ticket deleted successfully!')
    ]);
  }


  public function includeSlotSystemVariable($ticket_id)
  {
    $ticket = Ticket::find($ticket_id);
    $organizer_id = $ticket->event->organizer_id;

    if (!is_null($ticket->variations)) {
      $variations = json_decode($ticket->variations, true);
      foreach ($variations as &$vari) {
        if (!array_key_exists('slot_enable', $vari)) {
          $vari['slot_enable'] = 0;
        }
        if (!array_key_exists('slot_unique_id', $vari)) {
          $vari['slot_unique_id'] = rand(000000, 999999);
        }
        if (!array_key_exists('slot_seat_min_price', $vari)) {
          $vari['slot_seat_min_price'] = 0.00;
        }
      }
      unset($vari);
      $ticket->update([
        'variations' => json_encode($variations)
      ]);
    }

    if (is_null($ticket->normal_ticket_slot_unique_id)) {
      $ticket->update([
        'normal_ticket_slot_unique_id' => rand(000000, 999999),
      ]);
    }

    if (is_null($ticket->normal_ticket_slot_enable)) {
      $ticket->update([
        'normal_ticket_slot_enable' => 0,
      ]);
    }

    if (is_null($ticket->free_tickete_slot_unique_id)) {
      $ticket->update([
        'free_tickete_slot_unique_id' => rand(000000, 999999),
      ]);
    }

    if (is_null($ticket->free_tickete_slot_enable)) {
      $ticket->update([
        'free_tickete_slot_enable' => 0,
      ]);
    }

    if (is_null($ticket->slot_seat_min_price)) {
      $ticket->update([
        'slot_seat_min_price' => 0.00
      ]);
    }

    return true;
  }

  public function deleteRemovedSlotIds($variationData, $incomingSlotIds)
  {
    $existingSlotIds = [];

    // Collect slot IDs from variations
    if (!empty($variationData)) {
      $decodedVariations = json_decode($variationData, true);
      $existingSlotIds = array_merge($existingSlotIds, array_column($decodedVariations, 'slot_unique_id'));
    }

    // Normalize incoming slot IDs to integers
    $normalizedIncomingSlotIds = array_map('intval', $incomingSlotIds);

    // Find slot IDs that exist but were not included in request
    $slotIdsToDelete = array_diff($existingSlotIds, $normalizedIncomingSlotIds);

    // Delete related slots and images
    foreach ($slotIdsToDelete as $slotId) {
      $slot = Slot::where('slot_unique_id', $slotId)->first();
      if (!is_null($slot)) {
        $slot->delete();
      }

      $slotImage = SlotImage::where('slot_unique_id', $slotId)->first();
      if (!is_null($slotImage)) {
        @unlink(public_path('assets/admin/img/map-image/' . $slotImage->image));
        $slotImage->delete();
      }
    }
  }

  public function updateSlotIsEnable($slot_unique_id, $slot_enable_input)
  {
    Slot::query()->where('slot_unique_id', $slot_unique_id)->update([
      'slot_enable' => $slot_enable_input
    ]);
    return true;
  }
}
