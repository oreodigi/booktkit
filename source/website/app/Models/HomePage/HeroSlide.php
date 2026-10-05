<?php

namespace App\Models\HomePage;

use App\Models\Event;
use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeroSlide extends Model
{
    use HasFactory;

    protected $fillable = [
        'language_id','media_type','image','video','video_source','video_url','title','subtitle','button_text',
        'event_id','custom_url','open_new_tab','sort_order','status'
    ];

    protected $casts = ['open_new_tab' => 'boolean', 'status' => 'boolean'];

    public function language() { return $this->belongsTo(Language::class); }
    public function event() { return $this->belongsTo(Event::class); }
}
