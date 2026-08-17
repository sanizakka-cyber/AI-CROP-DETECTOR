<div class="flex items-center justify-between gap-4 flex-wrap">
    <div>
        <h2 class="font-extrabold text-xl text-gray-800 tracking-tight">Executive Command Center</h2>
        <p class="text-xs text-gray-400 mt-0.5 font-medium">MSAS FarmAI &nbsp;·&nbsp; Real-time Business Intelligence</p>
    </div>
    <div class="flex items-center gap-3">
        <span id="live-clock" class="text-sm font-bold text-slate-600 tabular-nums bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-200"></span>
        <a href="{{ route('ceo.reports') }}" class="text-xs font-semibold text-white bg-[#0F6B3E] hover:bg-[#0B2447] px-4 py-2 rounded-lg transition-colors">Export Reports →</a>
    </div>
</div>
<script>
(function tick() {
    const el = document.getElementById('live-clock');
    if (el) el.textContent = new Date().toLocaleTimeString('en-NG', {
        hour:'2-digit', minute:'2-digit', second:'2-digit', timeZone:'Africa/Lagos'
    });
    setTimeout(tick, 1000);
})();
</script>
