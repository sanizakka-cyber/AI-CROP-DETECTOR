<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('User Management') }}
        </h2>
    </x-slot>

    <div class="space-y-6">

        @if(session('success'))
            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-md">
                <p class="text-green-700 font-medium">{{ session('success') }}</p>
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-md">
                <p class="text-red-700 font-medium">{{ session('error') }}</p>
            </div>
        @endif

        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <!-- Search Form -->
            <form method="GET" action="{{ route('admin.users') }}" class="w-full md:w-2/3 flex gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, email, or phone..." class="w-full rounded-lg border-slate-300 focus:border-emerald-500 focus:ring focus:ring-emerald-200">
                <select name="role" class="rounded-lg border-slate-300 focus:border-emerald-500 focus:ring focus:ring-emerald-200">
                    <option value="">All Roles</option>
                    <option value="farmer" {{ request('role') == 'farmer' ? 'selected' : '' }}>Farmer</option>
                    <option value="vet" {{ request('role') == 'vet' ? 'selected' : '' }}>Vet</option>
                    <option value="agronomist" {{ request('role') == 'agronomist' ? 'selected' : '' }}>Agronomist</option>
                    <option value="finance" {{ request('role') == 'finance' ? 'selected' : '' }}>Finance</option>
                    <option value="rider" {{ request('role') == 'rider' ? 'selected' : '' }}>Rider</option>
                </select>
                <button type="submit" class="bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700 transition">Search</button>
            </form>

            <button onclick="document.getElementById('addUserModal').classList.remove('hidden')"
                    class="w-full md:w-auto bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition font-bold flex items-center justify-center gap-2">
                <span>➕</span> Add New User
            </button>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-slate-500 uppercase text-xs border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4">User</th>
                            <th class="px-6 py-4">Contact Info</th>
                            <th class="px-6 py-4">Role</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Joined</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($users as $user)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <img class="w-10 h-10 rounded-full object-cover shadow-sm border border-slate-100" src="{{ $user->avatarUrl }}" alt="" loading="lazy">
                                        <div>
                                            <div class="font-bold text-slate-800">{{ $user->name ?: $user->email }}</div>
                                            <div class="text-xs text-slate-500">ID: #{{ str_pad($user->id, 4, '0', STR_PAD_LEFT) }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-slate-800">{{ $user->email }}</div>
                                    <div class="text-xs text-slate-500">{{ $user->phone }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="capitalize font-bold text-slate-700 bg-slate-100 px-3 py-1 rounded-full text-xs border border-slate-200">
                                        {{ str_replace('_', ' ', $user->role) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    @if($user->is_active)
                                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 border border-green-200">Active</span>
                                    @else
                                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700 border border-red-200">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    {{ $user->created_at->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Toggle Status -->
                                        @if($user->role !== 'ceo' && $user->id !== auth()->id())
                                            <form action="{{ route('admin.users.toggle', $user) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-bold {{ $user->is_active ? 'bg-amber-100 text-amber-700 hover:bg-amber-200' : 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' }} transition">
                                                    {{ $user->is_active ? 'Suspend' : 'Activate' }}
                                                </button>
                                            </form>
                                            <!-- Delete -->
                                            <form action="{{ route('admin.users.delete', $user) }}" method="POST" onsubmit="return confirm('Are you sure you want to completely delete this user? This cannot be undone.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-red-100 text-red-700 hover:bg-red-200 transition">
                                                    Delete
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs text-slate-400 italic">Protected</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-slate-500">
                                    No users found matching your search.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="p-4 border-t border-slate-200">
                {{ $users->links() }}
            </div>
        </div>

    </div>

    {{-- ── Add New User Modal ─────────────────────────────────────────── --}}
    <div id="addUserModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <h3 class="text-lg font-bold text-slate-800">Add Staff Account</h3>
                <button onclick="document.getElementById('addUserModal').classList.add('hidden')"
                        class="text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
            </div>
            <form method="POST" action="{{ route('admin.users.store') }}" class="px-6 py-5 space-y-4">
                @csrf
                @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3">
                    @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
                </div>
                @endif
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">First Name</label>
                        <input type="text" name="first_name" value="{{ old('first_name') }}" required
                               class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Last Name</label>
                        <input type="text" name="last_name" value="{{ old('last_name') }}" required
                               class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Role</label>
                    <select name="role" required class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 text-sm">
                        <option value="">Select role…</option>
                        <option value="vet"              {{ old('role')=='vet'              ? 'selected' : '' }}>Veterinarian</option>
                        <option value="agronomist"       {{ old('role')=='agronomist'       ? 'selected' : '' }}>Agronomist</option>
                        <option value="admin"            {{ old('role')=='admin'            ? 'selected' : '' }}>Administrator</option>
                        <option value="finance"          {{ old('role')=='finance'          ? 'selected' : '' }}>Finance Officer</option>
                        <option value="hr"               {{ old('role')=='hr'               ? 'selected' : '' }}>Human Resources</option>
                        <option value="operations"       {{ old('role')=='operations'       ? 'selected' : '' }}>Operations Manager</option>
                        <option value="data-analyst"     {{ old('role')=='data-analyst'     ? 'selected' : '' }}>Data Analyst</option>
                        <option value="m-e-officer"      {{ old('role')=='m-e-officer'      ? 'selected' : '' }}>M&amp;E Officer</option>
                        <option value="field-officer"    {{ old('role')=='field-officer'    ? 'selected' : '' }}>Field Officer</option>
                        <option value="customer-support" {{ old('role')=='customer-support' ? 'selected' : '' }}>Customer Support</option>
                        <option value="extension-officer"{{ old('role')=='extension-officer'? 'selected' : '' }}>Extension Officer</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Password</label>
                        <input type="password" name="password" required
                               class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 text-sm"
                               placeholder="Min 8 chars, mixed case + number">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Confirm Password</label>
                        <input type="password" name="password_confirmation" required
                               class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 text-sm">
                    </div>
                </div>
                <p class="text-xs text-slate-500">The user will be prompted to change their password on first login.</p>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="document.getElementById('addUserModal').classList.add('hidden')"
                            class="px-4 py-2 rounded-lg border border-slate-300 text-slate-600 text-sm font-semibold hover:bg-slate-50">Cancel</button>
                    <button type="submit"
                            class="px-5 py-2 rounded-lg bg-indigo-600 text-white text-sm font-bold hover:bg-indigo-700 transition">Create Account</button>
                </div>
            </form>
        </div>
    </div>
    @if($errors->any())
    <script>document.addEventListener('DOMContentLoaded',()=>document.getElementById('addUserModal').classList.remove('hidden'));</script>
    @endif

</x-app-layout>
