@props(['logs', 'showPagination' => false])
<div class="space-y-4">
    @forelse ($logs as $log)
        @php
            $activity = strtolower($log->aktivitas);
            $icon = 'fa-clock-rotate-left';
            $bg = 'bg-slate-100';
            $text = 'text-slate-600';
            $badge = 'bg-slate-100 text-slate-700';
            if (str_contains($activity, 'login')) {
                $icon = 'fa-right-to-bracket';
                $bg = 'bg-green-100';
                $text = 'text-green-600';
                $badge = 'bg-green-100 text-green-700';
            } elseif (str_contains($activity, 'logout')) {
                $icon = 'fa-right-from-bracket';
                $bg = 'bg-red-100';
                $text = 'text-red-600';
                $badge = 'bg-red-100 text-red-700';
            } elseif (str_contains($activity, 'tambah')) {
                $icon = 'fa-circle-plus';
                $bg = 'bg-blue-100';
                $text = 'text-blue-600';
                $badge = 'bg-blue-100 text-blue-700';
            } elseif (
                str_contains($activity, 'edit') ||
                str_contains($activity, 'ubah') ||
                str_contains($activity, 'update')
            ) {
                $icon = 'fa-pen';
                $bg = 'bg-yellow-100';
                $text = 'text-yellow-600';
                $badge = 'bg-yellow-100 text-yellow-700';
            } elseif (str_contains($activity, 'hapus') || str_contains($activity, 'delete')) {
                $icon = 'fa-trash';
                $bg = 'bg-rose-100';
                $text = 'text-rose-600';
                $badge = 'bg-rose-100 text-rose-700';
            }
            $namaPengguna = $log->user?->nama ?? ($log->user?->username ?? 'Pengguna tidak diketahui');
        @endphp
        <div class="bg-white border border-slate-200 rounded-2xl p-5 hover:shadow-md transition">
            <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-5">
                {{-- Informasi aktivitas --}}
                <div class="flex gap-4 min-w-0">
                    <div class="w-12 h-12 rounded-xl {{ $bg }} flex items-center justify-center shrink-0">
                        <i class="fa-solid {{ $icon }} {{ $text }} text-lg"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        {{-- Identitas pengguna --}}
                        <div class="flex flex-wrap items-center gap-2 mb-2">
                            <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-700">
                                <i class="fa-solid fa-user text-slate-400"></i>
                                {{ $namaPengguna }}
                            </span>
                            @if ($log->user?->role)
                                <span class="text-xs px-2.5 py-1 rounded-full bg-purple-50 text-purple-700 font-medium">
                                    {{ ucwords(str_replace('_', ' ', $log->user->role)) }}
                                </span>
                            @endif
                            @if ($log->user?->nip)
                                <span class="text-xs text-slate-500">
                                    NIP: {{ $log->user->nip }}
                                </span>
                            @endif
                        </div>
                        {{-- Aktivitas --}}
                        <h3 class="font-semibold text-slate-800">
                            {{ $log->aktivitas }}
                        </h3>
                        <p class="text-sm text-slate-600 mt-1 leading-relaxed">
                            {{ $log->deskripsi }}
                        </p>
                        {{-- Informasi perangkat --}}
                        <div class="flex flex-wrap gap-x-5 gap-y-2 mt-3 text-xs text-slate-500">
                            @if ($log->ip_address)
                                <span class="inline-flex items-center gap-1.5">
                                    <i class="fa-solid fa-globe"></i>
                                    {{ $log->ip_address }}
                                </span>
                            @endif
                            @if ($log->user_agent)
                                <span class="inline-flex items-center gap-1.5">
                                    <i class="fa-solid fa-desktop"></i>
                                    {{ Str::limit($log->user_agent, 55) }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
                {{-- Status dan waktu --}}
                <div
                    class="flex flex-row lg:flex-col lg:items-end items-center justify-between lg:justify-start gap-3 lg:gap-2 shrink-0">
                    <span class="inline-flex px-3 py-1.5 rounded-full text-xs font-medium {{ $badge }}">
                        {{ $log->aktivitas }}
                    </span>
                    <div class="lg:text-right">
                        <p class="text-sm text-slate-500">
                            <i class="fa-regular fa-clock mr-1"></i>
                            {{ $log->created_at->diffForHumans() }}
                        </p>
                        <p class="text-xs text-slate-400 mt-1">
                            {{ $log->created_at->format('d M Y, H:i') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
            <i class="fa-solid fa-folder-open text-4xl text-slate-300 mb-4"></i>
            <h3 class="font-semibold text-slate-700">
                Belum Ada Aktivitas
            </h3>
            <p class="text-sm text-slate-500 mt-2">
                Riwayat aktivitas pengguna akan muncul di sini.
            </p>
        </div>
    @endforelse
</div>
@if ($showPagination && $logs instanceof \Illuminate\Contracts\Pagination\Paginator && $logs->hasPages())
    <div class="mt-6">
        {{ $logs->withQueryString()->links() }}
    </div>
@endif
