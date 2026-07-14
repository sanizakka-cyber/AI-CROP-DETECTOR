<x-app-layout>
    <x-slot name="header">Platform Reports</x-slot>

    {{-- Toast --}}
    <div id="toast" class="hidden fixed top-6 right-6 z-50 bg-slate-800 text-white text-sm font-semibold px-5 py-3 rounded-xl shadow-lg transition-all" role="alert">
        Export feature coming soon!
    </div>

    <script>
        function showToast(msg) {
            const t = document.getElementById('toast');
            t.textContent = msg || 'Coming soon!';
            t.classList.remove('hidden');
            setTimeout(() => t.classList.add('hidden'), 3000);
        }
    </script>

    <div class="space-y-6">

        {{-- Header --}}
        <div class="bg-gradient-to-r from-[#0F6B3E] to-slate-800 rounded-2xl p-8 text-white shadow-lg relative overflow-hidden">
            <div class="absolute right-0 top-0 w-56 h-56 bg-emerald-500/10 rounded-full blur-3xl"></div>
            <p class="text-emerald-200 text-sm mb-1">CEO Portal</p>
            <h1 class="text-3xl font-extrabold">Platform Reports</h1>
            <p class="text-emerald-100 text-sm mt-2">Generate and export comprehensive reports across all platform modules.</p>
        </div>

        {{-- Export Buttons --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <h3 class="font-bold text-slate-800 text-lg">Quick Export</h3>
                <div class="flex flex-wrap gap-2">
                    <button onclick="showToast('PDF export coming soon!')" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl text-sm font-semibold transition shadow-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        Export PDF
                    </button>
                    <button onclick="showToast('Excel export coming soon!')" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl text-sm font-semibold transition shadow-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export Excel
                    </button>
                    <button onclick="showToast('CSV export coming soon!')" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold transition shadow-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Export CSV
                    </button>
                </div>
            </div>
        </div>

        {{-- Report Category Cards --}}
        <div class="grid md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">

            {{-- Farmer Registration --}}
            <div class="bg-white border border-slate-200 p-6 rounded-2xl hover:border-[#1FA84A] hover:shadow-md transition group">
                <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center mb-4 group-hover:bg-[#0F6B3E] transition">
                    <svg class="w-6 h-6 text-[#0F6B3E] group-hover:text-white transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <h3 class="font-bold text-slate-800 text-base mb-1">Farmer Registration</h3>
                <p class="text-xs text-slate-500 mb-4">All registered farmers, demographics, and regional distribution.</p>
                <a href="{{ url('/ceo/reports/farmers') }}" class="block w-full py-2 bg-[#0F6B3E] text-white rounded-xl text-sm font-semibold text-center hover:bg-[#047857] transition">
                    &#9654; Generate
                </a>
            </div>

            {{-- Livestock --}}
            <div class="bg-white border border-slate-200 p-6 rounded-2xl hover:border-[#1FA84A] hover:shadow-md transition group">
                <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center mb-4 group-hover:bg-[#b45309] transition">
                    <svg class="w-6 h-6 text-[#b45309] group-hover:text-white transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                </div>
                <h3 class="font-bold text-slate-800 text-base mb-1">Livestock Inventory</h3>
                <p class="text-xs text-slate-500 mb-4">All registered livestock, species breakdown, and health records.</p>
                <a href="{{ url('/ceo/reports/livestock') }}" class="block w-full py-2 bg-[#0F6B3E] text-white rounded-xl text-sm font-semibold text-center hover:bg-[#047857] transition">
                    &#9654; Generate
                </a>
            </div>

            {{-- Poultry --}}
            <div class="bg-white border border-slate-200 p-6 rounded-2xl hover:border-[#1FA84A] hover:shadow-md transition group">
                <div class="w-12 h-12 rounded-xl bg-orange-100 flex items-center justify-center mb-4 group-hover:bg-orange-600 transition">
                    <svg class="w-6 h-6 text-orange-600 group-hover:text-white transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h3 class="font-bold text-slate-800 text-base mb-1">Poultry &amp; Egg Production</h3>
                <p class="text-xs text-slate-500 mb-4">Poultry flock sizes, egg production data, and breed distribution.</p>
                <a href="{{ url('/ceo/reports/animals') }}" class="block w-full py-2 bg-[#0F6B3E] text-white rounded-xl text-sm font-semibold text-center hover:bg-[#047857] transition">
                    &#9654; Generate
                </a>
            </div>

            {{-- Crop Production --}}
            <div class="bg-white border border-slate-200 p-6 rounded-2xl hover:border-[#1FA84A] hover:shadow-md transition group">
                <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center mb-4 group-hover:bg-green-700 transition">
                    <svg class="w-6 h-6 text-green-700 group-hover:text-white transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h3 class="font-bold text-slate-800 text-base mb-1">Crop Production</h3>
                <p class="text-xs text-slate-500 mb-4">Crop types, acreage, yield data, and agronomist interventions.</p>
                <a href="{{ url('/ceo/reports/animals') }}" class="block w-full py-2 bg-[#0F6B3E] text-white rounded-xl text-sm font-semibold text-center hover:bg-[#047857] transition">
                    &#9654; Generate
                </a>
            </div>

            {{-- Financial --}}
            <div class="bg-white border border-slate-200 p-6 rounded-2xl hover:border-[#1FA84A] hover:shadow-md transition group">
                <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center mb-4 group-hover:bg-[#0F6B3E] transition">
                    <svg class="w-6 h-6 text-[#0F6B3E] group-hover:text-white transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h3 class="font-bold text-slate-800 text-base mb-1">Financial Summary</h3>
                <p class="text-xs text-slate-500 mb-4">Income, expenses, net profit, and revenue by category.</p>
                <a href="{{ url('/ceo/reports/financial') }}" class="block w-full py-2 bg-[#0F6B3E] text-white rounded-xl text-sm font-semibold text-center hover:bg-[#047857] transition">
                    &#9654; Generate
                </a>
            </div>

            {{-- User Activity --}}
            <div class="bg-white border border-slate-200 p-6 rounded-2xl hover:border-[#1FA84A] hover:shadow-md transition group">
                <div class="w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center mb-4 group-hover:bg-indigo-700 transition">
                    <svg class="w-6 h-6 text-indigo-700 group-hover:text-white transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
                <h3 class="font-bold text-slate-800 text-base mb-1">User Activity</h3>
                <p class="text-xs text-slate-500 mb-4">Active users, login trends, and platform engagement metrics.</p>
                <a href="{{ url('/ceo/reports/users') }}" class="block w-full py-2 bg-[#0F6B3E] text-white rounded-xl text-sm font-semibold text-center hover:bg-[#047857] transition">
                    &#9654; Generate
                </a>
            </div>

            {{-- System Usage --}}
            <div class="bg-white border border-slate-200 p-6 rounded-2xl hover:border-[#1FA84A] hover:shadow-md transition group">
                <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center mb-4 group-hover:bg-slate-700 transition">
                    <svg class="w-6 h-6 text-slate-700 group-hover:text-white transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/></svg>
                </div>
                <h3 class="font-bold text-slate-800 text-base mb-1">System Usage</h3>
                <p class="text-xs text-slate-500 mb-4">AI scan usage, feature adoption, and module access stats.</p>
                <a href="{{ url('/ceo/reports/users') }}" class="block w-full py-2 bg-[#0F6B3E] text-white rounded-xl text-sm font-semibold text-center hover:bg-[#047857] transition">
                    &#9654; Generate
                </a>
            </div>

            {{-- Geographic --}}
            <div class="bg-white border border-slate-200 p-6 rounded-2xl hover:border-[#1FA84A] hover:shadow-md transition group">
                <div class="w-12 h-12 rounded-xl bg-teal-100 flex items-center justify-center mb-4 group-hover:bg-teal-700 transition">
                    <svg class="w-6 h-6 text-teal-700 group-hover:text-white transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <h3 class="font-bold text-slate-800 text-base mb-1">Geographic Distribution</h3>
                <p class="text-xs text-slate-500 mb-4">User and farmer distribution by state and LGA.</p>
                <a href="{{ url('/ceo/reports/geographic') }}" class="block w-full py-2 bg-[#0F6B3E] text-white rounded-xl text-sm font-semibold text-center hover:bg-[#047857] transition">
                    &#9654; Generate
                </a>
            </div>

            {{-- Disease Incidence --}}
            <div class="bg-white border border-slate-200 p-6 rounded-2xl hover:border-[#1FA84A] hover:shadow-md transition group">
                <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center mb-4 group-hover:bg-red-700 transition">
                    <svg class="w-6 h-6 text-red-700 group-hover:text-white transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <h3 class="font-bold text-slate-800 text-base mb-1">Disease Incidence</h3>
                <p class="text-xs text-slate-500 mb-4">Diagnosed diseases, outbreak trends, and regional alerts.</p>
                <a href="{{ url('/ceo/reports/diseases') }}" class="block w-full py-2 bg-[#0F6B3E] text-white rounded-xl text-sm font-semibold text-center hover:bg-[#047857] transition">
                    &#9654; Generate
                </a>
            </div>

            {{-- Expert Interventions --}}
            <div class="bg-white border border-slate-200 p-6 rounded-2xl hover:border-[#1FA84A] hover:shadow-md transition group">
                <div class="w-12 h-12 rounded-xl bg-purple-100 flex items-center justify-center mb-4 group-hover:bg-purple-700 transition">
                    <svg class="w-6 h-6 text-purple-700 group-hover:text-white transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <h3 class="font-bold text-slate-800 text-base mb-1">Expert Interventions</h3>
                <p class="text-xs text-slate-500 mb-4">Vet and agronomist consultations, response times, and outcomes.</p>
                <a href="{{ url('/ceo/reports/diseases') }}" class="block w-full py-2 bg-[#0F6B3E] text-white rounded-xl text-sm font-semibold text-center hover:bg-[#047857] transition">
                    &#9654; Generate
                </a>
            </div>

        </div>

        {{-- Recent Report Activity (Summary Table) --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">Recent Report Generations</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-bold text-slate-500 uppercase">
                            <th class="pb-3 pr-4">Report Type</th>
                            <th class="pb-3 pr-4">Generated By</th>
                            <th class="pb-3 pr-4">Format</th>
                            <th class="pb-3">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr class="hover:bg-slate-50">
                            <td class="py-3 pr-4 font-medium text-slate-800">Financial Summary</td>
                            <td class="py-3 pr-4 text-slate-600">Sani Yawale Zakka</td>
                            <td class="py-3 pr-4"><span class="px-2 py-0.5 bg-blue-100 text-blue-800 rounded-full text-xs font-semibold">PDF</span></td>
                            <td class="py-3 text-slate-500 text-xs">{{ now()->subDays(3)->format('M d, Y') }}</td>
                        </tr>
                        <tr class="hover:bg-slate-50">
                            <td class="py-3 pr-4 font-medium text-slate-800">User Activity</td>
                            <td class="py-3 pr-4 text-slate-600">Sani Yawale Zakka</td>
                            <td class="py-3 pr-4"><span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-full text-xs font-semibold">CSV</span></td>
                            <td class="py-3 text-slate-500 text-xs">{{ now()->subDays(7)->format('M d, Y') }}</td>
                        </tr>
                        <tr class="hover:bg-slate-50">
                            <td class="py-3 pr-4 font-medium text-slate-800">Livestock Inventory</td>
                            <td class="py-3 pr-4 text-slate-600">Sani Yawale Zakka</td>
                            <td class="py-3 pr-4"><span class="px-2 py-0.5 bg-amber-100 text-amber-800 rounded-full text-xs font-semibold">Excel</span></td>
                            <td class="py-3 text-slate-500 text-xs">{{ now()->subDays(14)->format('M d, Y') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>
