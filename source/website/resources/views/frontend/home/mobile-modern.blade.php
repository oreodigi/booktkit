@php
$mhSections=$mobileHomeCampaign?->sections ?? collect();
$mhDesign=$mobileHomeCampaign?->template?->design ?? [];
$mhAccent=$mhDesign['accent'] ?? '#f6b500';
$mhSurface=$mhDesign['surface'] ?? '#ffffff';
$mhRadius=$mhDesign['radius'] ?? 16;
$mhTemplate=$mobileHomeCampaign?->template?->template_key ?? 'modern';
$mhEvents=DB::table('event_contents')->join('events','events.id','=','event_contents.event_id')->where('event_contents.language_id',$currentLanguageInfo->id)->where('events.status',1)->where('events.end_date_time','>=',now())->orderByDesc('events.is_featured')->orderBy('events.start_date')->limit(6)->get();
@endphp
<div class="bkm-app bkm-template-{{ $mhTemplate }}" style="--bkm-accent:{{ $mhAccent }};--bkm-surface:{{ $mhSurface }};--bkm-radius:{{ $mhRadius }}px">
<header class="bkm-header"><a href="{{ route('index') }}" class="bkm-brand"><img src="{{ asset('assets/admin/img/' . $websiteInfo->logo) }}" alt="{{ $websiteInfo->website_title }}"></a><div class="bkm-actions"><a href="{{ route('contact') }}" aria-label="{{ __('Support') }}"><i class="fas fa-headphones"></i></a><a href="{{ Auth::guard('customer')->check()?route('customer.dashboard'):route('customer.login') }}" aria-label="{{ __('Account') }}"><i class="fas fa-user-circle"></i></a></div></header>
@foreach($mhSections as $section)
@if($section->type==='hero')
<section class="bkm-hero"><div class="bkm-hero-copy"><span class="bkm-kicker">{{ data_get($section->settings,'kicker',__('Discover. Book. Enjoy.')) }}</span><h1>{{ $section->title ?: __('Find your next experience') }}</h1><p>{{ data_get($section->settings,'subtitle',__('Concerts, festivals, sports and experiences — all in one place.')) }}</p></div><form class="bkm-search" action="{{ route('events') }}"><i class="far fa-search"></i><input name="search-input" placeholder="{{ data_get($section->settings,'search_placeholder',__('Search events, artists or venues')) }}"><button>{{ __('Search') }}</button></form></section>
@elseif($section->type==='quick_categories')
<section class="bkm-section"><div class="bkm-title"><h2>{{ $section->title }}</h2><a href="{{ route('events') }}">{{ __('See all') }}</a></div><div class="bkm-category-row">@php $ids=collect(explode(',',data_get($section->settings,'category_ids','')))->map(fn($v)=>(int)trim($v))->filter(); $sectionCategories=$ids->isNotEmpty()?$categories->whereIn('id',$ids)->sortBy(fn($c)=>$ids->search($c->id)):$categories->take(8); @endphp @foreach($sectionCategories as $category)<a href="{{ route('events',['category'=>$category->slug]) }}"><span><img src="{{ asset('assets/admin/img/event-category/'.$category->image) }}" alt="{{ $category->name }}"></span><strong>{{ $category->name }}</strong></a>@endforeach</div></section>
@elseif($section->type==='featured_events')
<section class="bkm-section"><div class="bkm-title"><h2>{{ $section->title }}</h2><a href="{{ route('events') }}">{{ __('See all') }}</a></div><div class="bkm-event-list">@php $ids=collect(explode(',',data_get($section->settings,'event_ids','')))->map(fn($v)=>(int)trim($v))->filter(); $limit=(int)data_get($section->settings,'limit',4); $sectionEvents=$ids->isNotEmpty()?$mhEvents->whereIn('id',$ids)->sortBy(fn($e)=>$ids->search($e->id))->take($limit):$mhEvents->take($limit); @endphp
@forelse($sectionEvents as $event)
@php
if ($event->date_type == 'multiple') { $event_date=eventLatestDates($event->id); $date=strtotime(@$event_date->start_date); $duration=@$event_date->duration; } else { $date=strtotime($event->start_date); $duration=$event->duration; }
$ticket=App\Models\Event\Ticket::where('event_id',$event->id)->where(function($q){$q->whereNotNull('price')->orWhereNotNull('f_price');})->orderBy('price')->first();
$organizer=$event->organizer_id?App\Models\Organizer::find($event->organizer_id):null;
$admin=$event->organizer_id?null:App\Models\Admin::first();
$organizerName=$organizer?->organizer_info?->name ?? $admin?->username;
$checkWishList=Auth::guard('customer')->check()?checkWishList($event->id,Auth::guard('customer')->user()->id):false;
@endphp
<div class="bkm-event">
<a class="bkm-event-image" href="{{ route('event.details',[$event->slug,$event->id]) }}"><img src="{{ asset('assets/admin/img/event/thumbnail/'.$event->thumbnail) }}" alt="{{ $event->title }}"></a>
<a href="{{ $checkWishList==false?route('addto.wishlist',$event->id):route('remove.wishlist',$event->id) }}" class="bkm-wishlist {{ $checkWishList?'is-saved':'' }}" aria-label="{{ __('Save event') }}"><i class="far fa-bookmark"></i></a>
<div class="bkm-event-info"><ul class="bkm-time-info"><li><i class="far fa-calendar-alt"></i><span>{{ \Carbon\Carbon::parse($date)->timezone($websiteInfo->timezone)->translatedFormat('d M') }}</span></li><li><i class="far fa-hourglass"></i><span>{{ $duration }}</span></li><li><i class="far fa-clock"></i><span>{{ \Carbon\Carbon::parse(strtotime($event->start_time))->timezone($websiteInfo->timezone)->translatedFormat('h:i A') }}</span></li></ul>
@if($organizerName)<div class="bkm-organizer">{{ __('By') }}&nbsp; {{ $organizerName }}</div>@endif
<h3><a href="{{ route('event.details',[$event->slug,$event->id]) }}">{{ $event->title }}</a></h3>
<div class="bkm-event-bottom"><span class="bkm-location"><i class="fas fa-map-marker-alt"></i>{{ $event->event_type==='venue'?($event->address ?: ($event->city ?? __('Venue Event'))):__('Online') }}</span><strong>{{ $ticket ? (($ticket->pricing_type ?? null)==='free'?__('Free'):symbolPrice($ticket->price ?? $ticket->f_price ?? 0)) : __('View tickets') }}</strong></div></div></div>
@empty<p class="bkm-empty">{{ __('New events are coming soon.') }}</p>@endforelse</div></section>
@elseif($section->type==='explore_categories')
<section class="bkm-section"><div class="bkm-title"><h2>{{ $section->title }}</h2></div><div class="bkm-explore">@php $ids=collect(explode(',',data_get($section->settings,'category_ids','')))->map(fn($v)=>(int)trim($v))->filter(); $sectionCategories=$ids->isNotEmpty()?$categories->whereIn('id',$ids)->sortBy(fn($c)=>$ids->search($c->id)):$categories; @endphp @foreach($sectionCategories as $category)<a href="{{ route('events',['category'=>$category->slug]) }}"><img src="{{ asset('assets/admin/img/event-category/'.$category->image) }}" alt="{{ $category->name }}"><span></span><strong>{{ $category->name }}</strong></a>@endforeach</div></section>
@elseif($section->type==='organizer_cta')
<section class="bkm-section"><div class="bkm-cta"><div><small>{{ __('FOR ORGANIZERS') }}</small><h2>{{ __('Hosting an event?') }}</h2><p>{{ data_get($section->settings,'text',__('Create your event and start selling tickets with BookTKIT.')) }}</p></div><a href="{{ route('organizer.signup') }}">{{ data_get($section->settings,'button',__('Start selling')) }}</a></div></section>
@elseif($section->type==='how_it_works')
<section class="bkm-section"><div class="bkm-title"><h2>{{ $section->title }}</h2></div><div class="bkm-steps"><div><i class="far fa-search"></i><b>{{ __('Discover') }}</b><small>{{ __('Find an event') }}</small></div><div><i class="fas fa-ticket-alt"></i><b>{{ __('Book') }}</b><small>{{ __('Choose tickets') }}</small></div><div><i class="fas fa-qrcode"></i><b>{{ __('Enter') }}</b><small>{{ __('Show your QR') }}</small></div></div></section>
@elseif($section->type==='partners' && isset($partners) && $partners->count())
<section class="bkm-section bkm-last"><div class="bkm-title"><h2>{{ $section->title }}</h2></div><div class="bkm-partners">@foreach($partners as $partner)<span><img src="{{ asset('assets/admin/img/partner/'.$partner->image) }}" alt="{{ $partner->name ?? __('Partner') }}"></span>@endforeach</div></section>
@endif
@endforeach
<nav class="bkm-nav"><a class="active" href="{{ route('index') }}"><i class="fas fa-home"></i><span>{{ __('Home') }}</span></a><a href="{{ route('events') }}"><i class="far fa-compass"></i><span>{{ __('Explore') }}</span></a><a href="{{ Auth::guard('customer')->check()?route('customer.booking.my_booking'):route('customer.login') }}"><i class="fas fa-ticket-alt"></i><span>{{ __('Tickets') }}</span></a><a href="{{ Auth::guard('customer')->check()?route('customer.dashboard'):route('customer.login') }}"><i class="far fa-user"></i><span>{{ __('Account') }}</span></a></nav>
</div>