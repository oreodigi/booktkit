<?php

namespace App\Http\Controllers\BackEnd\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Event\EventPassProduct;
use App\Models\Event\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EventPassController extends Controller
{
 public function index(int $eventId){$event=$this->event($eventId);return view('organizer.event.passes',['event'=>$event,'passes'=>EventPassProduct::with('dates')->where('event_id',$event->id)->orderBy('sort_order')->get(),'tickets'=>Ticket::where('event_id',$event->id)->get(),'dates'=>$event->dates()->orderBy('start_date')->get()]);}
 public function store(Request $r,int $eventId){$event=$this->event($eventId);$data=$this->validated($r,$event);DB::transaction(function()use($data,$event){$dates=$data['event_date_ids'];unset($data['event_date_ids']);$data['uuid']=(string)Str::uuid();$data['event_id']=$event->id;$data['organizer_id']=$event->organizer_id;$data['price']=(int)round(((float)$data['price'])*100);$pass=EventPassProduct::create($data);$pass->dates()->sync($dates);});return back()->with('success','Pass added successfully.');}
 public function update(Request $r,int $eventId,int $passId){$event=$this->event($eventId);$pass=EventPassProduct::where('event_id',$event->id)->findOrFail($passId);$data=$this->validated($r,$event,$pass);DB::transaction(function()use($data,$pass){$dates=$data['event_date_ids'];unset($data['event_date_ids']);$data['price']=(int)round(((float)$data['price'])*100);$pass->update($data);$pass->dates()->sync($dates);});return back()->with('success','Pass updated successfully.');}
 public function destroy(int $eventId,int $passId){$event=$this->event($eventId);$pass=EventPassProduct::where('event_id',$event->id)->findOrFail($passId);abort_if($pass->sold_quantity>0,409,'A sold pass cannot be deleted. Disable it instead.');$pass->delete();return back()->with('success','Pass deleted.');}
 private function event(int $id){return Event::where('organizer_id',Auth::guard('organizer')->id())->findOrFail($id);}
 private function validated(Request $r,Event $event,?EventPassProduct $pass=null){$id=$pass?->id;return $r->validate(['ticket_id'=>['required','integer',Rule::exists('tickets','id')->where(fn($q)=>$q->where('event_id',$event->id))],'code'=>['required','string','max:80',Rule::unique('event_pass_products','code')->where(fn($q)=>$q->where('event_id',$event->id))->ignore($id)],'name'=>'required|string|max:160','pass_type'=>'required|in:single_day,any_one_day,multi_day,weekend,combo,season,group,couple,custom','selection_mode'=>'required|in:fixed_dates,all_dates,choose_n','choose_count'=>'nullable|required_if:selection_mode,choose_n|integer|min:1|max:365','price'=>'required|numeric|min:0|max:9999999','inventory_type'=>'required|in:unlimited,limited','inventory_quantity'=>'nullable|required_if:inventory_type,limited|integer|min:1','max_per_order'=>'required|integer|min:1|max:50','admissions_per_holder'=>'required|integer|min:1|max:100','sales_channels'=>'required|array|min:1','sales_channels.*'=>'in:web,mobile,pos,box_office','credential_types'=>'nullable|array','credential_types.*'=>'in:digital_qr,printed_qr,qr_wristband,rfid_wristband,rfid_card,printed_id','event_date_ids'=>'required|array|min:1','event_date_ids.*'=>['integer',Rule::exists('event_dates','id')->where(fn($q)=>$q->where('event_id',$event->id))],'active'=>'sometimes|boolean','sort_order'=>'nullable|integer|min:0|max:999']);}
}
