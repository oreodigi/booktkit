<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class BoxOfficeLedgerEntry extends Model{protected $fillable=['sale_id','entry_type','account','amount','currency','reference','metadata'];protected $casts=['metadata'=>'array'];}