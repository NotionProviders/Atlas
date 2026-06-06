(function(){
const PAL = window.ATLAS_CONFIG.palette;
const TREE = window.ATLAS_CONFIG.tree;
const LEGEND = window.ATLAS_CONFIG.legend;

let UID=0; const byId={}; const nodes=[];
function build(n,parent,depth,inherit){n._id=n.id||('n'+(UID++));byId[n._id]=n;n.parent=parent;n.depth=depth;n.color=n.c||inherit||PAL.core;nodes.push(n);if(n.children)n.children.forEach(function(ch){build(ch,n,depth+1,n.color);});}
build(TREE,null,0,PAL.core);

function layoutRadial(n,x,y,R,ang){
  n.x=x;n.y=y;n._R=R;n._draw=R*0.14;
  const kids=n.children; if(!kids||!kids.length){n._ring=0;return;}
  const m=kids.length;
  const ringR=R*0.62; n._ring=ringR;
  const full=!n.parent;
  const span=full?2*Math.PI:Math.PI;
  const step=span/m;
  let childR=Math.min(ringR*Math.sin(step/2)*0.86,(R-ringR)*0.9);
  if(childR<R*0.02)childR=R*0.02;
  kids.forEach(function(c,i){
    const a=full?(ang+i*step):(ang-Math.PI/2+(i+0.5)*step);
    layoutRadial(c, x+Math.cos(a)*ringR, y+Math.sin(a)*ringR, childR, a);
  });
}
layoutRadial(TREE,0,0,5000,0);

const SVGNS="http://www.w3.org/2000/svg";
const gEdges=document.getElementById('edges'),gNodes=document.getElementById('nodes'),gStars=document.getElementById('stars'),world=document.getElementById('world'),app=document.getElementById('app');

(function(){let s=11;function r(){s=(s*9301+49297)%233280;return s/233280;}
  for(let i=0;i<170;i++){const c=document.createElementNS(SVGNS,'circle');
    c.setAttribute('cx',(r()*1700).toFixed(1));c.setAttribute('cy',(r()*1000).toFixed(1));
    c.setAttribute('r',(r()*1.1+.2).toFixed(2));c.setAttribute('fill','#fff');
    c.setAttribute('opacity',(r()*.5+.08).toFixed(2));gStars.appendChild(c);}
})();

const edges=[];
nodes.forEach(function(n){
  if(n.parent){
    const ln=document.createElementNS(SVGNS,'line');
    ln.setAttribute('x1',n.parent.x);ln.setAttribute('y1',n.parent.y);ln.setAttribute('x2',n.x);ln.setAttribute('y2',n.y);
    ln.setAttribute('class','edge');ln.setAttribute('stroke',n.color);ln.setAttribute('stroke-width',n.depth<=1?1.5:n.depth===2?1.1:0.8);
    gEdges.appendChild(ln); n._edge=ln; edges.push(n);
  }
});
nodes.forEach(function(n){
  const g=document.createElementNS(SVGNS,'g');g.setAttribute('class','node');
  const c=document.createElementNS(SVGNS,'circle');c.setAttribute('cx',n.x);c.setAttribute('cy',n.y);c.setAttribute('r',n._draw);
  if(n.kind==='core'){c.setAttribute('fill','url(#sun)');}
  else{c.setAttribute('fill',n.color);c.setAttribute('fill-opacity',0.92);}
  g.appendChild(c);
  const hasKids=n.children&&n.children.length;
  if(n.portal&&!hasKids){const ring=document.createElementNS(SVGNS,'circle');ring.setAttribute('cx',n.x);ring.setAttribute('cy',n.y);ring.setAttribute('r',n._draw*1.7);ring.setAttribute('fill','none');ring.setAttribute('stroke',n.color);ring.setAttribute('stroke-width',1);ring.setAttribute('stroke-dasharray','2 3');ring.setAttribute('vector-effect','non-scaling-stroke');g.appendChild(ring);n._ringEl=ring;}
  if(hasKids&&n.depth>=1){const dr=(n.depth===1)?n._draw*0.13:n._draw*0.26;const dot=document.createElementNS(SVGNS,'circle');dot.setAttribute('cx',n.x+n._draw*0.78);dot.setAttribute('cy',n.y-n._draw*0.78);dot.setAttribute('r',dr);dot.setAttribute('fill','#ff7a1a');dot.setAttribute('stroke','#ffffff');dot.setAttribute('stroke-width',1.2);dot.setAttribute('vector-effect','non-scaling-stroke');g.appendChild(dot);n._dotEl=dot;}
  const t=document.createElementNS(SVGNS,'text');t.setAttribute('x',n.x);
  t.setAttribute('class','nlabel'+((n.kind==='core'||n.kind==='domain')?' dom':''));
  t.textContent=n.label;g.appendChild(t);
  gNodes.appendChild(g);n._g=g;n._c=c;n._t=t;
  g.addEventListener('pointerdown',function(e){e.stopPropagation();});
  g.addEventListener('click',function(e){e.stopPropagation();onClick(n);});
});

let view={tx:0,ty:0,k:1,rot:0};
let focus=TREE,searchActive=false,selected=null;
const KMIN=0.02,KMAX=6000;
const LBL={0:21,1:15.5,2:13.5,3:12,4:11,5:10.5,6:10,7:9.5};
function vw(){return app.clientWidth;}function vh(){return app.clientHeight;}
function smooth(v,a,b){let t=(v-a)/(b-a);t=t<0?0:t>1?1:t;return t*t*(3-2*t);}
function clamp(x,a,b){return x<a?a:x>b?b:x;}
function s2w(sx,sy){const c=Math.cos(view.rot),s=Math.sin(view.rot);const rx=(sx-view.tx)/view.k,ry=(sy-view.ty)/view.k;return [rx*c+ry*s,-rx*s+ry*c];}
function fade(n,v){ if(searchActive)return n._match?v:v*0.1; return n._ctx?v:v*0.2; }
function ancestorBodyFade(n,k){
  if(n===focus||!n._ring)return 1;
  let p=focus;
  while(p){
    if(p.parent===n)return 1-smooth(n._ring*k,90,190);
    p=p.parent;
  }
  return 1;
}
function frame(){
  const rd=view.rot*180/Math.PI;
  world.setAttribute('transform','translate('+view.tx+','+view.ty+') scale('+view.k+') rotate('+rd+')');
  const k=view.k,tx=view.tx,ty=view.ty,W=vw(),H=vh(),c=Math.cos(view.rot),s=Math.sin(view.rot);
  const cand=[];
  for(let qi=0;qi<nodes.length;qi++){const n=nodes[qi];
    const rev=n.parent?smooth(n.parent._ring*k,90,200):1;n._rev=rev;
    const rx=n.x*c-n.y*s, ry=n.x*s+n.y*c, sx=rx*k+tx, sy=ry*k+ty, dp=n._draw*k;
    const vis=rev>0.012&&dp>0.35&&sx>-80&&sx<W+80&&sy>-80&&sy<H+80;
    if(!vis){if(n._g.style.display!=='none')n._g.style.display='none';if(n._edge)n._edge.setAttribute('stroke-opacity',0);continue;}
    if(n._g.style.display==='none')n._g.style.display='';
    const al=fade(n,rev);
    const body=al*ancestorBodyFade(n,k);
    n._c.setAttribute('opacity',body.toFixed(3));
    if(n._ringEl)n._ringEl.setAttribute('opacity',(body*smooth(dp,3,8)).toFixed(3));
    if(n._dotEl)n._dotEl.setAttribute('opacity',(body*smooth(dp,4,10)).toFixed(3));
    const lpx=12.5,fs=lpx/k,ly=n.y-n._draw-5/k;
    n._t.setAttribute('font-size',fs);n._t.setAttribute('y',ly);n._t.setAttribute('transform','rotate('+(-rd)+','+n.x+','+ly+')');
    let lop=fade(n,rev*smooth(dp,2.2,6.5));
    if(lop>0.04){const lrx=n.x*c-ly*s,lry=n.x*s+ly*c,lsx=lrx*k+tx,lsy=lry*k+ty,wpx=n.label.length*lpx*0.54+6,hpx=lpx*1.1+4;let pri=n.depth;if(n===selected)pri=-9;if(searchActive&&n._match)pri=-13;cand.push({n:n,lop:lop,pri:pri,box:{x:lsx-wpx/2,y:lsy-hpx,w:wpx,h:hpx}});}else{n._t.setAttribute('opacity',0);}
    if(n._edge){const base=n.depth<=1?0.42:n.depth===2?0.3:0.2;n._edge.setAttribute('stroke-opacity',fade(n,rev*base).toFixed(3));}
  }
  cand.sort(function(a,b){return a.pri-b.pri||b.lop-a.lop;});
  const placed=[];
  for(let ci=0;ci<cand.length;ci++){const c2=cand[ci];let hit=false;for(let i=0;i<placed.length;i++){const p=placed[i];if(c2.box.x<p.x+p.w&&c2.box.x+c2.box.w>p.x&&c2.box.y<p.y+p.h&&c2.box.y+c2.box.h>p.y){hit=true;break;}}if(hit){c2.n._t.setAttribute('opacity',0);}else{c2.n._t.setAttribute('opacity',c2.lop.toFixed(3));placed.push(c2.box);}}
}
function interpolateZoom(p0,p1){const rho=1.42,rho2=rho*rho,rho4=rho2*rho2;const ux0=p0[0],uy0=p0[1],w0=p0[2],ux1=p1[0],uy1=p1[1],w1=p1[2];const dx=ux1-ux0,dy=uy1-uy0,d2=dx*dx+dy*dy;let S,fn;if(d2<1e-9){S=Math.abs(Math.log(w1/w0))/rho;fn=function(t){return [ux0+t*dx,uy0+t*dy,w0*Math.pow(w1/w0,t)];};}else{const d1=Math.sqrt(d2);const b0=(w1*w1-w0*w0+rho4*d2)/(2*w0*rho2*d1);const b1=(w1*w1-w0*w0-rho4*d2)/(2*w1*rho2*d1);const r0=Math.log(Math.sqrt(b0*b0+1)-b0);const r1=Math.log(Math.sqrt(b1*b1+1)-b1);S=(r1-r0)/rho;fn=function(t){const ss=t*S,ch0=Math.cosh(r0);const u=w0/(rho2*d1)*(ch0*Math.tanh(rho*ss+r0)-Math.sinh(r0));return [ux0+u*dx,uy0+u*dy,w0*ch0/Math.cosh(rho*ss+r0)];};}fn.S=Math.abs(S);return fn;}
let anim=null;let rotAnim=null;
function flyTo(cx,cy,k,then){k=clamp(k,KMIN,KMAX);const W=vw(),H=vh();const cw=s2w(W/2,H/2);const p0=[cw[0],cw[1],W/view.k];const p1=[cx,cy,W/k];const iz=interpolateZoom(p0,p1);const dur=clamp(280+iz.S*240,440,1600);cancelAnimationFrame(anim);const t0=performance.now();(function step(now){let t=(now-t0)/dur;if(t>1)t=1;const e=t<.5?4*t*t*t:1-Math.pow(-2*t+2,3)/2;const q=iz(e);view.k=W/q[2];const co=Math.cos(view.rot),si=Math.sin(view.rot),rx=q[0]*co-q[1]*si,ry=q[0]*si+q[1]*co;view.tx=W/2-rx*view.k;view.ty=H/2-ry*view.k;frame();if(t<1)anim=requestAnimationFrame(step);else if(then)then();})(performance.now());}
function frameNode(n,then){let k;if(n.children&&n.children.length)k=0.84*Math.min(vw(),vh())/(2*n._R);else k=0.7*Math.min(vw(),vh())/(2*(n.parent?n.parent._ring:n._R*4));flyTo(n.x,n.y,k,then);}
function fitRoot(){flyTo(0,0,0.42*Math.min(vw(),vh())/TREE._R);}
function zoomAt(sx,sy,f){const k2=clamp(view.k*f,KMIN,KMAX);const wp=s2w(sx,sy);view.k=k2;const c=Math.cos(view.rot),s=Math.sin(view.rot),rx=wp[0]*c-wp[1]*s,ry=wp[0]*s+wp[1]*c;view.tx=sx-rx*k2;view.ty=sy-ry*k2;frame();}
function rotateBy(d){cancelAnimationFrame(rotAnim);const W=vw(),H=vh(),cw=s2w(W/2,H/2);view.rot+=d;const c=Math.cos(view.rot),s=Math.sin(view.rot),rx=cw[0]*c-cw[1]*s,ry=cw[0]*s+cw[1]*c;view.tx=W/2-rx*view.k;view.ty=H/2-ry*view.k;updateNorth();frame();}
function resetNorth(){animateRotTo(0);}function animateRotTo(target){cancelAnimationFrame(rotAnim);const W=vw(),H=vh(),pivot=s2w(W/2,H/2),start=view.rot,t0=performance.now(),dur=420;(function st(now){let t=(now-t0)/dur;if(t>1)t=1;const e=t<.5?4*t*t*t:1-Math.pow(-2*t+2,3)/2;view.rot=start+(target-start)*e;const c=Math.cos(view.rot),s=Math.sin(view.rot),rx=pivot[0]*c-pivot[1]*s,ry=pivot[0]*s+pivot[1]*c;view.tx=W/2-rx*view.k;view.ty=H/2-ry*view.k;updateNorth();frame();if(t<1)rotAnim=requestAnimationFrame(st);})(performance.now());}
function updateNorth(){const el=document.getElementById('needle');if(el)el.style.transform='rotate('+(-view.rot*180/Math.PI)+'deg)';}
function markCtx(f){nodes.forEach(function(n){n._ctx=false;});let p=f;while(p){p._ctx=true;p=p.parent;}(function dive(n){n._ctx=true;if(n.children)n.children.forEach(dive);})(f);}
function setFocus(n){focus=n;markCtx(n);updateChrome();}
function resetFocus(){closeSearch();setFocus(TREE);closeDrawer();frame();}
function domainOf(n){let p=n;while(p){if(p.kind==='domain'||p.kind==='core')return p;p=p.parent;}return TREE;}
function esc(str){return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
function updateChrome(){const cr=document.getElementById('crumbs');cr.innerHTML='';const path=[];let p=focus;while(p){path.unshift(p);p=p.parent;}path.forEach(function(node,i){if(i>0){const sp=document.createElement('span');sp.className='sep';sp.textContent='›';cr.appendChild(sp);}const el=document.createElement('span');el.className='c';el.innerHTML=i===path.length-1?('<b>'+esc(node.label)+'</b>'):esc(node.label);el.onclick=function(){setFocus(node);frameNode(node);};cr.appendChild(el);});}
function onClick(n){document.getElementById('hint').style.opacity=0;if(n.children&&n.children.length){setFocus(n);frameNode(n);closeDrawer();return;}if(n.portal&&byId[n.portal]){const tgt=byId[n.portal];flashArc(n,tgt);setFocus(tgt);frameNode(tgt,function(){if(!tgt.children||!tgt.children.length)openDrawer(tgt);});toast('bounced to '+tgt.label);return;}setFocus(n);frameNode(n,function(){openDrawer(n);});}
function flashArc(a,b){const p=document.createElementNS(SVGNS,'path');const mx=(a.x+b.x)/2,my=(a.y+b.y)/2-Math.hypot(b.x-a.x,b.y-a.y)*0.12;p.setAttribute('d','M '+a.x+' '+a.y+' Q '+mx+' '+my+' '+b.x+' '+b.y);p.setAttribute('fill','none');p.setAttribute('stroke',PAL.core);p.setAttribute('stroke-width','1.6');p.setAttribute('vector-effect','non-scaling-stroke');p.setAttribute('stroke-linecap','round');p.setAttribute('opacity','0.85');gEdges.appendChild(p);const t0=performance.now();(function st(now){const t=(now-t0)/820;p.setAttribute('opacity',(0.85*(1-t)).toFixed(3));if(t<1)requestAnimationFrame(st);else gEdges.removeChild(p);})(performance.now());}
const detail=document.getElementById('detail');
const KIND={core:'Workspace',domain:'Domain',sub:'Section',item:'Sidebar item',leaf:'Detail'};
function openDrawer(n){selected=n;const badge=document.getElementById('d-badge');badge.textContent=(KIND[n.kind]||'Node').toUpperCase();badge.style.color=n.color;document.getElementById('d-title').textContent=n.label;document.getElementById('d-lvl').textContent='Level '+n.depth+'  ·  '+domainOf(n).label;const body=document.getElementById('d-body');body.innerHTML='';if(n.note){const d=document.createElement('div');d.className='note';d.textContent=n.note;body.appendChild(d);}if(n.portal&&byId[n.portal]){const tgt=byId[n.portal];const b=document.createElement('div');b.className='portalbtn';b.style.color=n.color;b.innerHTML='<span class="big">↗</span><span>Bounce to <b>'+esc(tgt.label)+'</b><br><span style="color:var(--dim)">Level '+tgt.depth+' · '+esc(domainOf(tgt).label)+'</span></span>';b.onclick=function(){flashArc(n,tgt);setFocus(tgt);frameNode(tgt,function(){if(!tgt.children||!tgt.children.length)openDrawer(tgt);});};body.appendChild(b);}if(n.children&&n.children.length){const k=document.createElement('div');k.className='klabel';k.textContent='Inside ('+n.children.length+')';body.appendChild(k);const list=document.createElement('div');list.className='chips';n.children.forEach(function(ch){const sdiv=document.createElement('div');sdiv.className='s';sdiv.innerHTML='<span>'+esc(ch.label)+'</span>'+(ch.children?'<span class="a">'+ch.children.length+'</span>':(ch.portal?'<span class="a">↗</span>':''));sdiv.onclick=function(){setFocus(ch);frameNode(ch,function(){if(!ch.children||!ch.children.length)openDrawer(ch);});};list.appendChild(sdiv);});body.appendChild(list);}detail.classList.add('open');}
function closeDrawer(){detail.classList.remove('open');selected=null;}
document.getElementById('dx').onclick=closeDrawer;
const regWrap=document.getElementById('regions');
LEGEND.forEach(function(pair){const id=pair[0],name=pair[1];const n=byId[id];if(!n)return;const r=document.createElement('div');r.className='reg';r.innerHTML='<span class="dot" style="color:'+n.color+';background:'+n.color+'"></span><span class="nm">'+name+'</span>';r.onclick=function(){setFocus(n);frameNode(n);closeDrawer();};regWrap.appendChild(r);});
const stage=document.getElementById('stage');
const drag={on:false,sx:0,sy:0,lx:0,ly:0,moved:false,rotate:false,pid:null};
const pts=new Map();
const input={pinch:false,lastDist:0,panning:false,panId:null};
function endDrag(){
  if(drag.pid!=null){try{stage.releasePointerCapture(drag.pid);}catch(err){}}
  drag.on=false;drag.pid=null;
}
function ptDist(){const p=Array.from(pts.values());return Math.hypot(p[0].x-p[1].x,p[0].y-p[1].y);}
function ptMid(){const p=Array.from(pts.values());return {x:(p[0].x+p[1].x)/2,y:(p[0].y+p[1].y)/2};}
function pointerOnUi(x,y){
  const el=document.elementFromPoint(x,y);
  if(!el||!el.closest)return true;
  return !!(el.closest('.node')||el.closest('.hud')||el.closest('#detail')||el.closest('#help')||el.closest('#search-hud')||el.closest('input')||el.closest('button')||el.closest('textarea'));
}
function beginPinch(){
  input.panning=false;input.panId=null;
  endDrag();
  input.pinch=true;
  input.lastDist=ptDist();
  stage.classList.remove('grabbing');
}
function applyPinch(){
  const dist=ptDist();
  if(input.lastDist>0){
    const mid=ptMid(),r=app.getBoundingClientRect();
    zoomAt(mid.x-r.left,mid.y-r.top,dist/input.lastDist);
    drag.moved=true;
  }
  input.lastDist=dist;
}
function beginPan(e){
  input.panning=true;input.panId=e.pointerId;drag.moved=false;
  drag.sx=drag.lx=e.clientX;drag.sy=drag.ly=e.clientY;
  drag.rotate=e.pointerType==='mouse'&&(e.shiftKey||e.button===2);
  stage.classList.add('grabbing');
  if(e.pointerType==='mouse'){
    drag.on=true;drag.pid=e.pointerId;
    stage.setPointerCapture(e.pointerId);
  }
}
app.addEventListener('pointerdown',function(e){
  if(e.pointerType==='mouse'&&e.button!==0)return;
  const searchHud=document.getElementById('search-hud');
  if(searchHud&&searchHud.classList.contains('open')&&(!e.target.closest||!e.target.closest('#search-hud')))closeSearch();
  pts.set(e.pointerId,{x:e.clientX,y:e.clientY});
  if(pts.size>=2){e.preventDefault();beginPinch();return;}
  if(pts.size===1&&!input.pinch&&!pointerOnUi(e.clientX,e.clientY))beginPan(e);
},{capture:true});
app.addEventListener('pointermove',function(e){
  if(!pts.has(e.pointerId))return;
  pts.set(e.pointerId,{x:e.clientX,y:e.clientY});
  if(pts.size>=2){
    e.preventDefault();
    if(!input.pinch)beginPinch();
    else applyPinch();
    return;
  }
  if(!input.panning||e.pointerId!==input.panId||pts.size!==1)return;
  if(e.pointerType!=='mouse')e.preventDefault();
  const dx=e.clientX-drag.lx,dy=e.clientY-drag.ly;
  drag.lx=e.clientX;drag.ly=e.clientY;
  if(Math.abs(e.clientX-drag.sx)+Math.abs(e.clientY-drag.sy)>3)drag.moved=true;
  if(drag.rotate){rotateBy(dx*0.008);}else{view.tx+=dx;view.ty+=dy;frame();}
},{capture:true,passive:false});
app.addEventListener('pointerup',function(e){
  const wasPinch=input.pinch;
  pts.delete(e.pointerId);
  if(pts.size<2){input.pinch=false;input.lastDist=0;}
  if(pts.size===1&&wasPinch){
    input.panning=false;input.panId=null;
  }else if(pts.size===0){
    if(input.panning&&!drag.moved&&!wasPinch)resetFocus();
    input.panning=false;input.panId=null;
    endDrag();
    stage.classList.remove('grabbing');
  }
},{capture:true});
app.addEventListener('pointercancel',function(e){
  pts.delete(e.pointerId);
  if(pts.size<2){input.pinch=false;input.lastDist=0;}
  if(pts.size===0){
    input.panning=false;input.panId=null;
    endDrag();
    stage.classList.remove('grabbing');
  }
},{capture:true});
app.addEventListener('gesturestart',function(e){e.preventDefault();},{capture:true,passive:false});
stage.addEventListener('contextmenu',function(e){e.preventDefault();});
stage.addEventListener('wheel',function(e){e.preventDefault();const r=app.getBoundingClientRect();zoomAt(e.clientX-r.left,e.clientY-r.top,Math.pow(1.0016,-e.deltaY));},{passive:false});
const searchHud=document.getElementById('search-hud');
const searchToggle=document.getElementById('search-toggle');
const search=document.getElementById('search');
function closeSearch(){
  if(searchHud.classList.contains('open')){
    searchHud.classList.remove('open');
    searchToggle.setAttribute('aria-expanded','false');
    search.blur();
  }
  if(searchActive||search.value){
    search.value='';searchActive=false;
    nodes.forEach(function(n){n._match=false;});
    frame();
  }
}
function openSearch(){
  searchHud.classList.add('open');
  searchToggle.setAttribute('aria-expanded','true');
  search.focus();
}
searchToggle.onclick=function(e){e.stopPropagation();if(searchHud.classList.contains('open'))closeSearch();else openSearch();};
search.addEventListener('input',function(){const q=search.value.trim().toLowerCase();searchActive=q.length>0;nodes.forEach(function(n){n._match=q&&n.label.toLowerCase().indexOf(q)>=0;});frame();});
search.addEventListener('keydown',function(e){if(e.key==='Enter'){const q=search.value.trim().toLowerCase();if(!q)return;const hit=nodes.find(function(n){return n.label.toLowerCase().indexOf(q)>=0&&n.kind!=='core';});if(hit){setFocus(hit);frameNode(hit,function(){if(!hit.children||!hit.children.length)openDrawer(hit);});}}if(e.key==='Escape'){closeSearch();}});
document.getElementById('home').onclick=function(){setFocus(TREE);closeDrawer();fitRoot();};
document.getElementById('out').onclick=function(){const p=focus.parent||TREE;setFocus(p);closeDrawer();frameNode(p);};
document.getElementById('zin').onclick=function(){zoomAt(vw()/2,vh()/2,1.45);};
document.getElementById('zout').onclick=function(){zoomAt(vw()/2,vh()/2,1/1.45);};
document.getElementById('rccw').onclick=function(){animateRotTo(view.rot-Math.PI/12);};
document.getElementById('rcw').onclick=function(){animateRotTo(view.rot+Math.PI/12);};
document.getElementById('north').onclick=function(){resetNorth();};
document.getElementById('help-btn').onclick=function(){document.getElementById('help').classList.add('open');};
document.getElementById('help-close').onclick=function(){document.getElementById('help').classList.remove('open');};
document.getElementById('help').addEventListener('click',function(e){if(e.target.id==='help')e.currentTarget.classList.remove('open');});
let tt;function toast(m){const e=document.getElementById('toast');e.textContent=m;e.classList.add('show');clearTimeout(tt);tt=setTimeout(function(){e.classList.remove('show');},1200);}
window.addEventListener('keydown',function(e){if(e.target.tagName==='INPUT')return;if(e.key==='Escape'){if(document.getElementById('help').classList.contains('open'))document.getElementById('help').classList.remove('open');else resetFocus();}if(e.key==='Backspace'){e.preventDefault();const p=focus.parent||TREE;setFocus(p);closeDrawer();frameNode(p);}if(e.key==='h'||e.key==='H'){setFocus(TREE);closeDrawer();fitRoot();}if(e.key==='['){animateRotTo(view.rot-Math.PI/12);}if(e.key===']'){animateRotTo(view.rot+Math.PI/12);}});
window.addEventListener('resize',frame);
setFocus(TREE);updateNorth();
view.k=0.28*Math.min(vw(),vh())/TREE._R;view.tx=vw()/2;view.ty=vh()/2;frame();
fitRoot();
setTimeout(function(){document.getElementById('hint').style.opacity='';},400);
})();