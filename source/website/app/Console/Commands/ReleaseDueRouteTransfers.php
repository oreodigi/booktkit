<?php
namespace App\Console\Commands;
use App\Models\Payments\PaymentTransfer;
use App\Services\Payments\RazorpayRouteService;
use Illuminate\Console\Command;
class ReleaseDueRouteTransfers extends Command {
 protected $signature='payments:release-route-holds'; protected $description='Release due Razorpay Route settlement holds';
 public function handle(RazorpayRouteService $route): int {
  PaymentTransfer::where('on_hold',true)->whereNotNull('hold_release_at')->where('hold_release_at','<=',now())->orderBy('id')->chunkById(100,function($rows)use($route){
   foreach($rows as $row){try{$route->releaseHold($row);}catch(\Throwable $e){$row->update(['last_error'=>$e->getMessage()]);report($e);}}
  }); return self::SUCCESS;
 }
}