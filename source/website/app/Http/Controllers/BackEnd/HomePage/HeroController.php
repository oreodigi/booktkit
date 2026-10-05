<?php

namespace App\Http\Controllers\BackEnd\HomePage;

use App\Http\Controllers\Controller;
use App\Models\Event\EventContent;
use App\Models\HomePage\HeroSection;
use App\Models\HomePage\HeroSlide;
use App\Models\Language;
use App\Rules\ImageMimeTypeRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class HeroController extends Controller
{
    private function dir(): string
    {
        $dir = public_path('assets/admin/img/hero-slides/');
        if (!File::isDirectory($dir)) File::makeDirectory($dir, 0775, true);
        return $dir;
    }

    private function upload($file): string
    {
        $name = Str::uuid().'.'.strtolower($file->getClientOriginalExtension());
        $file->move($this->dir(), $name);
        return $name;
    }

    private function remove(?string $name): void
    {
        if ($name && File::exists($this->dir().$name)) File::delete($this->dir().$name);
    }

    public function index(Request $request)
    {
        $language = Language::where('code', $request->language)->firstOrFail();
        $information['language'] = $language;
        $information['data'] = $language->heroSec()->first();
        $information['slides'] = HeroSlide::where('language_id', $language->id)->orderBy('sort_order')->orderBy('id')->get();
        $information['events'] = EventContent::query()
            ->join('events', 'events.id', '=', 'event_contents.event_id')
            ->where('event_contents.language_id', $language->id)
            ->where('events.status', 1)
            ->select('event_contents.event_id', 'event_contents.title')
            ->orderBy('event_contents.title')->get();
        $information['langs'] = Language::all();
        $information['themeInfo'] = DB::table('basic_settings')->select('theme_version')->first();
        return view('backend.home-page.hero-section', $information);
    }

    public function storeSlide(Request $request)
    {
        $language = Language::where('code', $request->language)->firstOrFail();
        $rules = [
            'media_type' => 'required|in:image,video',
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:1000',
            'button_text' => 'nullable|string|max:80',
            'event_id' => 'nullable|exists:events,id',
            'custom_url' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'image' => $request->media_type === 'image' ? ['required', new ImageMimeTypeRule()] : ['nullable', new ImageMimeTypeRule()],
            'video' => $request->media_type === 'video' ? 'required|file|mimes:mp4,webm,mov|max:51200' : 'nullable'
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) return back()->withErrors($validator)->withInput();

        HeroSlide::create([
            'language_id' => $language->id,
            'media_type' => $request->media_type,
            'image' => $request->hasFile('image') ? $this->upload($request->file('image')) : null,
            'video' => $request->hasFile('video') ? $this->upload($request->file('video')) : null,
            'title' => $request->title,
            'subtitle' => $request->subtitle,
            'button_text' => $request->button_text,
            'event_id' => $request->event_id,
            'custom_url' => $request->custom_url,
            'open_new_tab' => $request->boolean('open_new_tab'),
            'sort_order' => $request->integer('sort_order', 0),
            'status' => $request->boolean('status', true),
        ]);
        Session::flash('success', 'Banner added successfully');
        return back();
    }

    public function updateSlide(Request $request, HeroSlide $slide)
    {
        $rules = [
            'media_type' => 'required|in:image,video',
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:1000',
            'button_text' => 'nullable|string|max:80',
            'event_id' => 'nullable|exists:events,id',
            'custom_url' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'image' => ['nullable', new ImageMimeTypeRule()],
            'video' => 'nullable|file|mimes:mp4,webm,mov|max:51200',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) return back()->withErrors($validator);

        $data = $request->only(['media_type','title','subtitle','button_text','event_id','custom_url','sort_order']);
        $data['open_new_tab'] = $request->boolean('open_new_tab');
        $data['status'] = $request->boolean('status');
        if ($request->hasFile('image')) { $this->remove($slide->image); $data['image'] = $this->upload($request->file('image')); }
        if ($request->hasFile('video')) { $this->remove($slide->video); $data['video'] = $this->upload($request->file('video')); }
        $slide->update($data);
        Session::flash('success', 'Banner updated successfully');
        return back();
    }

    public function destroySlide(HeroSlide $slide)
    {
        $this->remove($slide->image);
        $this->remove($slide->video);
        $slide->delete();
        Session::flash('success', 'Banner deleted successfully');
        return back();
    }

    // Kept for backwards compatibility with the original single hero record.
    public function update(Request $request)
    {
        $language = Language::where('code', $request->language)->firstOrFail();
        $heroInfo = $language->heroSec()->first();
        $payload = $request->only(['first_title','second_title','first_button','first_button_url','second_button','second_button_url','video_url']);
        if ($heroInfo) $heroInfo->update($payload);
        else HeroSection::create($payload + ['language_id' => $language->id]);
        Session::flash('success', 'Legacy hero text updated successfully');
        return back();
    }
}
