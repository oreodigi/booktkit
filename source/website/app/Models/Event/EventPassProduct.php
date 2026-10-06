<?php

namespace App\Models\Event;

use Illuminate\Database\Eloquent\Model;

class EventPassProduct extends Model
{
    protected $fillable = ['uuid','event_id','organizer_id','ticket_id','code','name','pass_type','selection_mode','choose_count','price','inventory_type','inventory_quantity','sold_quantity','max_per_order','admissions_per_holder','sales_channels','credential_types','active','sort_order','metadata'];
    protected $casts = ['sales_channels'=>'array','credential_types'=>'array','metadata'=>'array','active'=>'boolean'];

    public function event() { return $this->belongsTo(\App\Models\Event::class); }
    public function ticket() { return $this->belongsTo(Ticket::class); }
    public function dates() { return $this->belongsToMany(EventDates::class, 'event_pass_dates', 'pass_product_id', 'event_date_id')->withTimestamps(); }
}
