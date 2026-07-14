<x-app-layout>
    <x-slot name="header">New Support Ticket</x-slot>

    <div class="space-y-6 max-w-2xl mx-auto">

        <div class="bg-gradient-to-r from-slate-900 to-blue-800 rounded-2xl p-6 text-white">
            <p class="text-blue-200 text-sm mb-1">Customer Support</p>
            <h1 class="text-2xl font-extrabold">Create Ticket</h1>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <form method="POST" action="{{ route('support.tickets.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Customer (optional)</label>
                    <select name="user_id" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Walk-in / Anonymous</option>
                        @foreach($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name ?: $u->email }} ({{ $u->roleLabel }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Subject *</label>
                    <input type="text" name="subject" required value="{{ old('subject') }}"
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Category *</label>
                        <select name="category" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select...</option>
                            <option value="App Technical Issue">App Technical Issue</option>
                            <option value="Login &amp; Access">Login &amp; Access</option>
                            <option value="Marketplace Query">Marketplace Query</option>
                            <option value="AI Scan Query">AI Scan Query</option>
                            <option value="Subscription &amp; Billing">Subscription &amp; Billing</option>
                            <option value="General Enquiry">General Enquiry</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Priority *</label>
                        <select name="priority" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Description *</label>
                    <textarea name="description" required rows="5" placeholder="Describe the issue in detail..."
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-semibold hover:bg-blue-700 transition">Create Ticket</button>
                    <a href="{{ route('support.tickets') }}" class="flex-1 py-2.5 bg-slate-100 text-slate-700 rounded-xl text-sm font-semibold hover:bg-slate-200 transition text-center">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
