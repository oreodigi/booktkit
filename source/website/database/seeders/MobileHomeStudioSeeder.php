<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\MobileHomeTemplate;
use App\Models\MobileHomeCampaign;
use App\Models\MobileHomeSection;
class MobileHomeStudioSeeder extends Seeder {
 public function run(): void {
  $templates=[
   ['name'=>'BookTKIT Modern','slug'=>'booktkit-modern','template_key'=>'modern','design'=>['accent'=>'#f6b500','surface'=>'#ffffff','radius'=>16]],
   ['name'=>'Festival','slug'=>'festival','template_key'=>'festival','design'=>['accent'=>'#ff9f1c','surface'=>'#fffaf1','radius'=>18]],
   ['name'=>'Nightlife','slug'=>'nightlife','template_key'=>'nightlife','design'=>['accent'=>'#f6b500','surface'=>'#111217','radius'=>16]],
   ['name'=>'Sports','slug'=>'sports','template_key'=>'sports','design'=>['accent'=>'#f6b500','surface'=>'#ffffff','radius'=>14]],
   ['name'=>'Minimal','slug'=>'minimal','template_key'=>'minimal','design'=>['accent'=>'#f6b500','surface'=>'#ffffff','radius'=>12]],
  ];
  foreach($templates as $t) MobileHomeTemplate::updateOrCreate(['slug'=>$t['slug']],$t+['is_active'=>true]);

  $modern=MobileHomeTemplate::where('slug','booktkit-modern')->firstOrFail();
  $campaign=MobileHomeCampaign::updateOrCreate(
   ['name'=>'BookTKIT Default Mobile'],
   ['template_id'=>$modern->id,'status'=>'published','priority'=>0,'is_default'=>true]
  );
  $defaults=[
   ['hero','Discover Events',1],
   ['quick_categories','Event Categories',2],
   ['featured_events','Featured Events',3],
   ['explore_categories','Explore Categories',4],
   ['organizer_cta','Host an Event',5],
   ['how_it_works','How It Works',6],
   ['partners','Partners',7],
  ];
  foreach($defaults as $d){
   MobileHomeSection::updateOrCreate(
    ['campaign_id'=>$campaign->id,'type'=>$d[0]],
    ['title'=>$d[1],'position'=>$d[2],'enabled'=>true]
   );
  }
 }
}
