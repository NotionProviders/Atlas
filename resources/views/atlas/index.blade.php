@extends('layouts.atlas')

@section('content')
<div id="app">
  <div id="stage">
    <svg id="svg" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <filter id="glow" x="-100%" y="-100%" width="300%" height="300%">
          <feDropShadow dx="0" dy="0" stdDeviation="2.5" flood-color="#060a14" flood-opacity="0.35"/>
          <feGaussianBlur in="SourceGraphic" stdDeviation="3.2" result="b"/>
          <feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge>
        </filter>
        <radialGradient id="sun" cx="50%" cy="50%" r="50%">
          <stop offset="0%" stop-color="#ffe9a8"/><stop offset="42%" stop-color="#ffd066"/><stop offset="100%" stop-color="#c49228"/>
        </radialGradient>
      </defs>
      <g id="stars"></g>
      <g id="world"><g id="edges"></g><g id="nodes"></g></g>
    </svg>
  </div>

  <div class="hud" id="top">
    <div class="brand">
      <div class="brand-row">
        <span class="kicker">{{ $kicker }}</span>
        @if (!empty($workspaces) && count($workspaces) > 1)
        <select class="ws-switch" id="ws-switch" aria-label="Switch workspace"
                onchange="if(this.value)window.location.search='?w='+this.value">
          @foreach ($workspaces as $ws)
            <option value="{{ $ws['slug'] }}" @selected($ws['slug'] === $activeSlug)>{{ $ws['name'] }}</option>
          @endforeach
        </select>
        @endif
        <a class="ws-add" href="{{ route('workspaces.index') }}" title="Add or manage workspaces">+ Workspace</a>
      </div>
      <div class="crumbs" id="crumbs"></div>
    </div>
  </div>

  <div class="hud" id="search-hud">
    <button type="button" class="btn search-toggle" id="search-toggle" title="Search" aria-label="Search" aria-expanded="false" aria-controls="search-popup">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
    </button>
    <div class="search-popup" id="search-popup">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
      <input id="search" placeholder="Search the atlas…  &#8629; to fly" autocomplete="off" />
    </div>
  </div>

  <div class="hud" id="help-hud">
    <div class="legend-wrap" id="legend">
      <button type="button" class="btn legend-toggle" id="legend-toggle" title="Domains" aria-label="Domains" aria-expanded="false" aria-controls="legend-panel">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
      </button>
      <div class="legend-panel" id="legend-panel">
        <div class="legend-sub">click to fly</div>
        <div id="regions"></div>
      </div>
    </div>
    <button type="button" class="btn" id="help-btn" title="Guide" aria-label="Guide">?</button>
  </div>

  <div class="hud" id="ctrl">
    <div class="ctrl-more" id="ctrl-more">
      <div class="ctrl-panel" id="ctrl-panel">
        <button class="btn" id="out" title="Up one level" aria-label="Up one level">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 19h10"/><path d="M12 15V8"/><path d="M9 11l3-3 3 3"/></svg>
        </button>
        <button class="btn" id="zin" title="Zoom in">+</button>
        <button class="btn" id="zout" title="Zoom out">&#8722;</button>
        <button class="btn" id="rccw" title="Rotate left">&#8634;</button>
        <button class="btn" id="rcw" title="Rotate right">&#8635;</button>
        <button class="btn" id="north" title="Reset to north" aria-label="Reset to north">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.75" opacity="0.55"/><g id="needle"><path d="M12 5 15 14 12 11.5 9 14Z" fill="currentColor" stroke="none"/></g></svg>
        </button>
      </div>
      <button type="button" class="btn ctrl-toggle" id="ctrl-toggle" title="Navigation" aria-label="Navigation" aria-expanded="false" aria-controls="ctrl-panel">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76" fill="currentColor" stroke="currentColor" stroke-linejoin="round"/></svg>
      </button>
    </div>
    <button class="btn" id="home" title="Home">&#8962;</button>
  </div>

  <div class="hint" id="hint"><span class="hint-desk">scroll zoom · drag pan · shift-drag or &#8634; &#8635; to rotate · click empty space to reset</span><span class="hint-touch">pinch zoom · drag pan · tap empty space to reset</span></div>

  <div id="detail">
    <span class="x" id="dx">&times;</span>
    <span class="badge" id="d-badge">NODE</span>
    <h2 id="d-title">—</h2>
    <div class="lvlrow" id="d-lvl"></div>
    <div id="d-body"></div>
  </div>

  <div id="help">
    <div class="helpcard">
      <h3>How This Atlas Works</h3>
      <div class="sub">An interactive map of a Notion workspace. Zoom from the whole workspace down through sidebar, teamspaces, pages, and blocks.</div>
      <div class="helprow"><span class="kx">scroll</span><span>Zoom toward the cursor. Orbits fade in as you get closer.</span></div>
      <div class="helprow"><span class="kx">drag</span><span>Pan around the map. Click or tap empty space to reset focus.</span></div>
      <div class="helprow"><span class="kx">click</span><span>Fly to a body. Zooms in when it has children.</span></div>
      <div class="helprow"><span class="kx">panel</span><span>Leaf nodes open a detail panel with notes and shortcuts inside.</span></div>
      <div class="helprow"><span class="kx">search</span><span>Magnifier button, top right. Type to highlight matches; press Enter to fly.</span></div>
      <div class="helprow"><span class="kx">domains</span><span>Domains button, bottom left. Jump to sidebar, teamspaces, databases, and more.</span></div>
      <div class="helprow"><span class="kx">orange dot</span><span>More inside this body. Click to zoom in or keep scrolling.</span></div>
      <div class="helprow"><span class="kx">dotted ring</span><span>Portal — bounces to where that feature actually lives.</span></div>
      <div class="helprow"><span class="kx">home</span><span>Home button, bottom right. Fits the whole workspace.</span></div>
      <div class="helprow"><span class="kx">level up</span><span>Up-from-line arrow in the nav menu. Go one level higher.</span></div>
      <div class="helprow"><span class="kx">north</span><span>Compass in the nav menu. Orange needle shows orientation; click to reset to north.</span></div>
      <div class="helprow"><span class="kx">crumbs</span><span>Breadcrumb path at the top. Click any step to jump back.</span></div>
      <div class="close" id="help-close">CLOSE</div>
    </div>
  </div>

  <div class="toast" id="toast">copied</div>
</div>
@endsection

@push('scripts')
<script>{!! $atlasConfigScript !!}</script>
<script src="{{ asset('js/atlas.js') }}?v={{ @filemtime(public_path('js/atlas.js')) ?: 1 }}" defer></script>
@endpush
