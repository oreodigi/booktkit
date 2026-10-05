<?php

namespace App\Models\Event;

use App\Models\Event;
use Illuminate\Database\Eloquent\Model;

class BoxOfficeLocation extends Model
{
    protected $fillable = ['event_id', 'name', 'address', 'active'];
    protected $casts = ['active' => 'boolean'];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
