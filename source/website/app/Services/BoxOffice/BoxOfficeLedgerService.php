<?php
namespace App\Services\BoxOffice;use App\Models\BoxOfficeLedgerEntry;use App\Models\BoxOfficeSale;
class BoxOfficeLedgerService{
 public function recordSale(BoxOfficeSale $s):void{foreach([['counter_receipt','counter', $s->customer_total],['organizer_receivable','organizer',-$s->organizer_amount],['platform_fee_receivable','booktkit',-$s->platform_fee]] as $r)BoxOfficeLedgerEntry::firstOrCreate(['sale_id'=>$s->id,'entry_type'=>$r[0]],['account'=>$r[1],'amount'=>$r[2],'currency'=>$s->currency,'reference'=>$s->uuid]);}
 public function reverse(BoxOfficeSale $s):void{foreach([['counter_receipt_reversal','counter',-$s->customer_total],['organizer_receivable_reversal','organizer',$s->organizer_amount],['platform_fee_reversal','booktkit',$s->platform_fee]] as $r)BoxOfficeLedgerEntry::firstOrCreate(['sale_id'=>$s->id,'entry_type'=>$r[0]],['account'=>$r[1],'amount'=>$r[2],'currency'=>$s->currency,'reference'=>$s->uuid]);}
}