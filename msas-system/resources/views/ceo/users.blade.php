<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manage Users') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if(session('success'))
                        <div class="mb-4 bg-green-100 text-green-700 p-3 rounded-lg text-sm font-semibold">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-600">
                            <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
                                <tr>
                                    <th class="px-4 py-3 rounded-l-lg">Name</th>
                                    <th class="px-4 py-3">Email</th>
                                    <th class="px-4 py-3">Role</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3 rounded-r-lg text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $user)
                                    <tr class="border-b border-slate-50 hover:bg-slate-50 transition">
                                        <td class="px-4 py-3 font-semibold text-slate-800">{{ $user->name }}</td>
                                        <td class="px-4 py-3">{{ $user->email }}</td>
                                        <td class="px-4 py-3 capitalize">
                                            <span class="px-2 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                                                {{ $user->role }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($user->is_active)
                                                <span class="px-2 py-1 rounded-full text-[10px] font-bold bg-green-100 text-green-700">Active</span>
                                            @else
                                                <span class="px-2 py-1 rounded-full text-[10px] font-bold bg-red-100 text-red-700">Inactive</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                @if($user->id !== auth()->id() && !session('impersonate.original_id'))
                                                    <a href="{{ route('impersonate.start', $user) }}"
                                                       class="px-3 py-1 rounded-lg text-xs font-bold bg-violet-100 text-violet-700 hover:bg-violet-200 transition"
                                                       onclick="return confirm('Login as {{ addslashes($user->first_name . ' ' . $user->last_name) }} ({{ $user->role }})?')">
                                                        Login As
                                                    </a>
                                                @elseif($user->id === auth()->id())
                                                    <span class="text-xs text-slate-400 italic">You</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
