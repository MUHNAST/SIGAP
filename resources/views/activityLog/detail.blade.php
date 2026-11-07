@extends('layouts.app')
@section('content')
    <div class="w-full p-4 md:p-6">
        <div class="flex flex-col gap-4 mb-6 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">{{ $title }}</h1>
                <p class="mt-1 text-slate-500">Rincian aktivitas yang tercatat pada sistem.</p>
            </div>
            <a href="{{ route('activityLog.index') }}"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium border rounded-xl border-slate-300 hover:bg-slate-50">
                <i class="fa-solid fa-arrow-left"></i>
                Kembali ke riwayat aktivitas
            </a>
        </div>
        <x-activity-card :logs="collect([$activityLog])" />
    </div>
@endsection
