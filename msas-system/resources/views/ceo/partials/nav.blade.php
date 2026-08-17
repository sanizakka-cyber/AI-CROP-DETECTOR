{{-- Shared CEO dashboard section nav — real routed links, active state via routeIs() --}}
<style>
.bi-nav-bar { position:sticky; top:0; z-index:40; background:#fff; border-bottom:1px solid #e2e8f0; box-shadow:0 1px 4px rgba(0,0,0,.06); overflow-x:auto; }
.bi-nav-bar::-webkit-scrollbar { display:none; }
.bi-nav-inner { display:flex; min-width:max-content; }
.bi-nav-link { padding:11px 16px; font-size:11px; font-weight:700; color:#64748b; white-space:nowrap; border-bottom:2px solid transparent; text-decoration:none; transition:color .15s, border-color .15s; letter-spacing:.03em; text-transform:uppercase; }
.bi-nav-link:hover { color:#0F6B3E; }
.bi-nav-link.bi-active { color:#0F6B3E; border-bottom-color:#0F6B3E; }
</style>
<div class="bi-nav-bar rounded-xl">
    <div class="bi-nav-inner">
        <a class="bi-nav-link {{ request()->routeIs('ceo.overview')     ? 'bi-active' : '' }}" href="{{ route('ceo.overview') }}">Overview</a>
        <a class="bi-nav-link {{ request()->routeIs('ceo.risk-center')  ? 'bi-active' : '' }}" href="{{ route('ceo.risk-center') }}">Risk Center</a>
        <a class="bi-nav-link {{ request()->routeIs('ceo.financial')    ? 'bi-active' : '' }}" href="{{ route('ceo.financial') }}">Financial</a>
        <a class="bi-nav-link {{ request()->routeIs('ceo.ai-analytics') ? 'bi-active' : '' }}" href="{{ route('ceo.ai-analytics') }}">AI Analytics</a>
        <a class="bi-nav-link {{ request()->routeIs('ceo.marketplace')  ? 'bi-active' : '' }}" href="{{ route('ceo.marketplace') }}">Marketplace</a>
        <a class="bi-nav-link {{ request()->routeIs('ceo.operations')   ? 'bi-active' : '' }}" href="{{ route('ceo.operations') }}">Operations</a>
        <a class="bi-nav-link {{ request()->routeIs('ceo.geographic')   ? 'bi-active' : '' }}" href="{{ route('ceo.geographic') }}">Geographic</a>
        <a class="bi-nav-link {{ request()->routeIs('ceo.users-subs')   ? 'bi-active' : '' }}" href="{{ route('ceo.users-subs') }}">Users & Subs</a>
        <a class="bi-nav-link {{ request()->routeIs('ceo.system')       ? 'bi-active' : '' }}" href="{{ route('ceo.system') }}">System</a>
    </div>
</div>
