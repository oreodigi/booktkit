<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\MobileHomeTemplate;
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
 }
}
