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
      <h1>{{ $heading }}</h1>
      <div class="rowline"><span class="lvl" id="lvl">L0 · Workspace</span><span class="crumbs" id="crumbs"></span></div>
    </div>
    <div class="search">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
      <input id="search" placeholder="Search the atlas…  &#8629; to fly" autocomplete="off" />
    </div>
  </div>

  <div class="hud" id="legend"><div class="lh">Domains · click to fly</div><div id="regions"></div></div>

  <div class="hud" id="ctrl">
    <button class="btn lab" id="help-btn">? GUIDE</button>
    <button class="btn" id="home" title="Home">&#8962;</button>
    <button class="btn" id="out" title="Up one level">&#8593;</button>
    <button class="btn" id="rccw" title="Rotate left">&#8634;</button>
    <button class="btn" id="rcw" title="Rotate right">&#8635;</button>
    <button class="btn" id="north" title="Reset to north"><span id="needle">&#8593;</span></button>
    <button class="btn" id="zin" title="Zoom in">+</button>
    <button class="btn" id="zout" title="Zoom out">&#8722;</button>
  </div>

  <div class="hint" id="hint">scroll zoom · drag pan · shift-drag or &#8634; &#8635; to rotate · click empty space to reset</div>

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
<script>
  window.ATLAS_CONFIG = {!! \Illuminate\Support\Js::from($atlasConfig) !!};
</script>
<script src="{{ asset('js/atlas.js') }}" defer></script>
@endpush
