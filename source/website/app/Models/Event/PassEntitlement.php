<?php

namespace App\Models\Event;

use Illuminate\Database\Eloquent\Model;

class PassEntitlement extends Model
{
 protected $fillable=['issued_ticket_id','pass_product_id','event_date_id','status'];
 public function issuedTicket(){return $this->belongsTo(IssuedTicket::class);}
 public function passProduct(){return $this->belongsTo(EventPassProduct::class);}
 public function eventDate(){return $this->belongsTo(EventDates::class);}
}
