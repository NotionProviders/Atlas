@extends('layouts.atlas')

@section('content')
<div id="app">
  <div id="stage">
    <svg id="svg" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <filter id="glow" x="-80%" y="-80%" width="260%" height="260%">
          <feGaussianBlur stdDeviation="3.4" result="b"/><feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge>
        </filter>
        <radialGradient id="sun" cx="50%" cy="50%" r="50%">
          <stop offset="0%" stop-color="#fff3d0"/><stop offset="45%" stop-color="#ffd66b"/><stop offset="100%" stop-color="#b8842b"/>
        </radialGradient>
      </defs>
      <g id="stars"></g>
      <g id="world"><g id="edges"></g><g id="nodes"></g></g>
    </svg>
  </div>

  <div class="hud" id="top">
    <div class="brand">
      <span class="kicker">{{ $kicker }}</span>
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

  <div class="hud" id="legend">
    <div class="legend-panel" id="legend-panel">
      <div class="legend-sub">click to fly</div>
      <div id="regions"></div>
    </div>
    <button type="button" class="btn lab legend-toggle" id="legend-toggle" aria-expanded="false" aria-controls="legend-panel">
      <span>Domains</span>
      <svg class="legend-chev" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
    </button>
  </div>

  <div class="hud" id="ctrl">
    <button class="btn lab" id="help-btn">? GUIDE</button>
    <div class="ctrl-more" id="ctrl-more">
      <div class="ctrl-panel" id="ctrl-panel">
        <button class="btn" id="out" title="Up one level">&#8593;</button>
        <button class="btn" id="rccw" title="Rotate left">&#8634;</button>
        <button class="btn" id="rcw" title="Rotate right">&#8635;</button>
        <button class="btn" id="north" title="Reset to north"><span id="needle">&#8593;</span></button>
        <button class="btn" id="zin" title="Zoom in">+</button>
        <button class="btn" id="zout" title="Zoom out">&#8722;</button>
      </div>
      <button type="button" class="btn ctrl-toggle" id="ctrl-toggle" title="More controls" aria-label="More controls" aria-expanded="false" aria-controls="ctrl-panel">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="5" r="1.5" fill="currentColor" stroke="none"/><circle cx="12" cy="12" r="1.5" fill="currentColor" stroke="none"/><circle cx="12" cy="19" r="1.5" fill="currentColor" stroke="none"/></svg>
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
      <h3>How this atlas works</h3>
      <div class="sub">A Notion workspace drawn as a solar system. The workspace is the sun; each domain orbits it; their parts orbit them, all the way down to a single block. Depth equals zoom.</div>
      <div class="helprow"><span class="kx">scroll</span><span>Zoom toward the cursor. A body's orbiting children stay hidden until you get close, then fade in. Keep going to fall level after level.</span></div>
      <div class="helprow"><span class="kx">drag</span><span>Pan around the current orbit.</span></div>
      <div class="helprow"><span class="kx">click</span><span>Fly to a body. Leaves open a detail panel.</span></div>
      <div class="helprow"><span class="kx">dotted ring</span><span>A portal. It bounces you smoothly to where that feature actually lives. Try Manage connections deep inside a page's ••• menu.</span></div>
      <div class="helprow"><span class="kx">orange dot</span><span>There is more inside. Click or zoom in to open it.</span></div>
      <div class="helprow"><span class="kx">⌂ ↑</span><span>Home returns to the workspace. Up climbs one orbit.</span></div>
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
