@extends('backend.layout')
@includeIf('backend.partials.rtl-style')

@section('content')
<div class="page-header">
  <h4 class="page-title">{{ __('Hero Banners') }}</h4>
  <ul class="breadcrumbs"><li class="nav-home"><a href="{{ route('admin.dashboard') }}"><i class="flaticon-home"></i></a></li><li class="separator"><i class="flaticon-right-arrow"></i></li><li class="nav-item">{{ __('Home Page') }}</li><li class="separator"><i class="flaticon-right-arrow"></i></li><li class="nav-item">{{ __('Hero Banners') }}</li></ul>
</div>

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between">
        <div><div class="card-title">{{ __('Homepage Banner Slider') }}</div><small class="text-muted">{{ __('Add multiple image/video banners. Active banners rotate automatically on desktop and mobile.') }}</small></div>
        <div style="min-width:160px">@includeIf('backend.partials.languages')</div>
      </div>
      <div class="card-body">
        <form action="{{ route('admin.home_page.hero_slides.store', ['language'=>request('language')]) }}" method="POST" enctype="multipart/form-data">
          @csrf
          <div class="row">
            <div class="col-lg-3"><div class="form-group"><label>{{ __('Media Type') }} *</label><select name="media_type" class="form-control hero-media-type"><option value="image">{{ __('Image') }}</option><option value="video">{{ __('Video') }}</option></select></div></div>
            <div class="col-lg-3 hero-image-field"><div class="form-group"><label>{{ __('Banner Image') }}</label><input type="file" name="image" class="form-control" accept="image/*"><small class="text-muted">{{ __('Recommended 1920×720. For video, this can be the poster image.') }}</small></div></div>
            <div class="col-lg-3 hero-video-field" style="display:none"><div class="form-group"><label>{{ __('Video Source') }}</label><select name="video_source" class="form-control hero-video-source"><option value="upload">{{ __('Upload Video') }}</option><option value="youtube">{{ __('YouTube Link') }}</option><option value="url">{{ __('Direct Video Link') }}</option></select></div></div>
            <div class="col-lg-3 hero-video-upload" style="display:none"><div class="form-group"><label>{{ __('Background Video') }}</label><input type="file" name="video" class="form-control" accept="video/mp4,video/webm,video/quicktime"><small class="text-muted">{{ __('MP4/WebM/MOV, maximum 5 MB. Video autoplays muted and loops.') }}</small></div></div>
            <div class="col-lg-6 hero-video-url" style="display:none"><div class="form-group"><label>{{ __('Video Link') }}</label><input type="url" name="video_url" class="form-control ltr" placeholder="https://www.youtube.com/watch?v=..."><small class="text-muted">{{ __('YouTube controls are hidden for background playback. Direct links should point to a browser-playable video file.') }}</small></div></div>
            <div class="col-lg-3"><div class="form-group"><label>{{ __('Order') }}</label><input type="number" min="0" name="sort_order" value="{{ ($slides->max('sort_order') ?? 0) + 10 }}" class="form-control"></div></div>
            <div class="col-lg-6"><div class="form-group"><label>{{ __('Title') }}</label><input name="title" class="form-control" placeholder="{{ __('Banner headline') }}"></div></div>
            <div class="col-lg-6"><div class="form-group"><label>{{ __('Subtitle') }}</label><input name="subtitle" class="form-control" placeholder="{{ __('Short supporting text') }}"></div></div>
            <div class="col-lg-3"><div class="form-group"><label>{{ __('Button Text') }}</label><input name="button_text" class="form-control" value="{{ __('View Event') }}"></div></div>
            <div class="col-lg-4"><div class="form-group"><label>{{ __('Connect Event') }}</label><select name="event_id" class="form-control"><option value="">{{ __('No event / use custom URL') }}</option>@foreach($events as $event)<option value="{{ $event->event_id }}">{{ $event->title }}</option>@endforeach</select><small class="text-muted">{{ __('When selected, the button opens this event automatically.') }}</small></div></div>
            <div class="col-lg-4"><div class="form-group"><label>{{ __('Custom Button URL') }}</label><input name="custom_url" class="form-control ltr" placeholder="/events or https://..."><small class="text-muted">{{ __('Used only when no event is connected.') }}</small></div></div>
            <div class="col-lg-1"><div class="form-group"><label>{{ __('Active') }}</label><div><label><input type="checkbox" name="status" value="1" checked> {{ __('Yes') }}</label></div></div></div>
          </div>
          <button class="btn btn-primary"><i class="fas fa-plus"></i> {{ __('Add Banner') }}</button>
        </form>
      </div>
    </div>

    @forelse($slides as $slide)
    <div class="card mb-3">
      <div class="card-body">
        <form action="{{ route('admin.home_page.hero_slides.update', $slide) }}" method="POST" enctype="multipart/form-data">
          @csrf
          <div class="row align-items-start">
            <div class="col-lg-3 mb-3">
              <div style="height:145px;border-radius:10px;overflow:hidden;background:#111">
                @if($slide->media_type==='video' && $slide->video)
                  <video src="{{ asset('assets/admin/img/hero-slides/'.$slide->video) }}" @if($slide->image) poster="{{ asset('assets/admin/img/hero-slides/'.$slide->image) }}" @endif muted playsinline style="width:100%;height:100%;object-fit:cover"></video>
                @elseif($slide->image)
                  <img src="{{ asset('assets/admin/img/hero-slides/'.$slide->image) }}" style="width:100%;height:100%;object-fit:cover" alt="">
                @endif
              </div>
              <small class="text-muted">{{ strtoupper($slide->media_type) }} · #{{ $slide->id }}</small>
            </div>
            <div class="col-lg-9"><div class="row">
              <div class="col-md-3"><div class="form-group"><label>{{ __('Media Type') }}</label><select name="media_type" class="form-control hero-media-type"><option value="image" @selected($slide->media_type==='image')>{{ __('Image') }}</option><option value="video" @selected($slide->media_type==='video')>{{ __('Video') }}</option></select></div></div>
              <div class="col-md-3 hero-image-field"><div class="form-group"><label>{{ __('Replace Image / Poster') }}</label><input type="file" name="image" class="form-control" accept="image/*"></div></div>
              <div class="col-md-3 hero-video-field" @if($slide->media_type!=='video') style="display:none" @endif><div class="form-group"><label>{{ __('Video Source') }}</label><select name="video_source" class="form-control hero-video-source"><option value="upload" @selected(($slide->video_source ?: 'upload')==='upload')>{{ __('Upload Video') }}</option><option value="youtube" @selected($slide->video_source==='youtube')>{{ __('YouTube Link') }}</option><option value="url" @selected($slide->video_source==='url')>{{ __('Direct Video Link') }}</option></select></div></div>
              <div class="col-md-3 hero-video-upload"><div class="form-group"><label>{{ __('Replace Video') }}</label><input type="file" name="video" class="form-control" accept="video/mp4,video/webm,video/quicktime"><small class="text-muted">{{ __('Maximum 5 MB') }}</small></div></div>
              <div class="col-md-6 hero-video-url"><div class="form-group"><label>{{ __('Video Link') }}</label><input type="url" name="video_url" value="{{ $slide->video_url }}" class="form-control ltr" placeholder="https://..."></div></div>
              <div class="col-md-3"><div class="form-group"><label>{{ __('Order') }}</label><input type="number" min="0" name="sort_order" value="{{ $slide->sort_order }}" class="form-control"></div></div>
              <div class="col-md-6"><div class="form-group"><label>{{ __('Title') }}</label><input name="title" value="{{ $slide->title }}" class="form-control"></div></div>
              <div class="col-md-6"><div class="form-group"><label>{{ __('Subtitle') }}</label><input name="subtitle" value="{{ $slide->subtitle }}" class="form-control"></div></div>
              <div class="col-md-3"><div class="form-group"><label>{{ __('Button Text') }}</label><input name="button_text" value="{{ $slide->button_text }}" class="form-control"></div></div>
              <div class="col-md-4"><div class="form-group"><label>{{ __('Connect Event') }}</label><select name="event_id" class="form-control"><option value="">{{ __('No event') }}</option>@foreach($events as $event)<option value="{{ $event->event_id }}" @selected($slide->event_id==$event->event_id)>{{ $event->title }}</option>@endforeach</select></div></div>
              <div class="col-md-4"><div class="form-group"><label>{{ __('Custom URL') }}</label><input name="custom_url" value="{{ $slide->custom_url }}" class="form-control ltr"></div></div>
              <div class="col-md-1"><div class="form-group"><label>{{ __('Active') }}</label><div><input type="checkbox" name="status" value="1" @checked($slide->status)></div></div></div>
              <div class="col-12"><button class="btn btn-success btn-sm">{{ __('Save Banner') }}</button></div>
            </div></div>
          </div>
        </form>
        <form action="{{ route('admin.home_page.hero_slides.destroy', $slide) }}" method="POST" class="mt-2" onsubmit="return confirm('{{ __('Delete this banner?') }}')">@csrf<button class="btn btn-danger btn-sm">{{ __('Delete') }}</button></form>
      </div>
    </div>
    @empty
      <div class="alert alert-info">{{ __('No slider banners yet. Add the first banner above. Until then, the existing hero banner remains on the website.') }}</div>
    @endforelse
  </div>
</div>
@endsection

@section('script')
<script>
document.querySelectorAll('form').forEach(function(form){
  var type=form.querySelector('.hero-media-type'),source=form.querySelector('.hero-video-source'); if(!type)return;
  function sync(){var isVideo=type.value==='video',v=source?source.value:'upload';form.querySelectorAll('.hero-video-field').forEach(function(el){el.style.display=isVideo?'block':'none'});form.querySelectorAll('.hero-video-upload').forEach(function(el){el.style.display=isVideo&&v==='upload'?'block':'none'});form.querySelectorAll('.hero-video-url').forEach(function(el){el.style.display=isVideo&&v!=='upload'?'block':'none'});}
  type.addEventListener('change',sync);if(source)source.addEventListener('change',sync);sync();
});
</script>
@endsection
