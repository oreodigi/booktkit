<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\Event\BoxOfficeLocation;
use App\Models\Organizer;
use App\Services\Events\EventActor;
use App\Services\Events\EventFormService;
use Illuminate\Support\Facades\DB;
use Tests\RefreshLegacyDatabase;
use Tests\TestCase;

class SpecialEventPhase2Test extends TestCase
{
    use RefreshLegacyDatabase;

    public function test_existing_event_defaults_remain_non_box_office(): void
    {
        $event=Event::create(['thumbnail'=>'qa.png','event_type'=>'venue']);
        $this->assertFalse((bool)$event->fresh()->box_office_enabled);
        $this->assertSame('none',$event->fresh()->reentry_policy);
    }

    public function test_special_event_remains_venue_and_syncs_locations(): void
    {
        $organizer=Organizer::create(['username'=>uniqid('qa'),'email'=>uniqid('qa').'@example.invalid','password'=>bcrypt('x')]);
        $event=Event::create(['thumbnail'=>'qa.png','organizer_id'=>$organizer->id,'event_type'=>'venue','box_office_enabled'=>true,'reentry_policy'=>'limited','max_reentries'=>2]);
        BoxOfficeLocation::create(['event_id'=>$event->id,'name'=>'Gate A','address'=>'North']);
        $this->assertSame('venue',$event->event_type);
        $this->assertTrue((bool)$event->box_office_enabled);
        $this->assertDatabaseHas('box_office_locations',['event_id'=>$event->id,'name'=>'Gate A']);
    }

    public function test_online_event_normalization_forces_box_office_off(): void
    {
        $service=app(EventFormService::class);
        $method=new \ReflectionMethod($service,'normalizeEventData'); $method->setAccessible(true);
        $data=$method->invoke($service,['event_type'=>'online','date_type'=>'single','start_date'=>'2026-11-01','start_time'=>'10:00','end_date'=>'2026-11-01','end_time'=>'11:00','box_office_enabled'=>true,'reentry_policy'=>'unlimited']);
        $this->assertFalse($data['box_office_enabled']);
        $this->assertSame('none',$data['reentry_policy']);
        $this->assertNull($data['max_reentries']);
    }
}
