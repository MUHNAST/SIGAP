@extends('layouts.app')
@section('content')
    <div class="p-6">
        <!-- ACCOUNT SETTINGS -->
        <div class="p-6 bg-slate-100 min-h-screen">
            <!-- PAGE HEADER -->
            <div class="bg-white rounded-2xl border border-slate-200 px-6 py-4 mb-6">
                <h1 class="text-xl font-semibold text-slate-800">
                    Pengaturan Akun
                </h1>
            </div>
            @include('components.alert')
            <!-- SETTINGS CARD -->
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <!-- TAB -->
                <div class="flex border-b border-slate-200">
                    <button onclick="showTab('account')" id="tab-account"
                        class="tab-btn px-6 py-4 border-b-2 border-blue-600 text-blue-600 font-medium">
                        <i class="fa-regular fa-user mr-2"></i>
                        Akun
                    </button>
                    <button onclick="showTab('security')" id="tab-security" class="tab-btn px-6 py-4 text-slate-500">
                        <i class="fa-solid fa-lock mr-2"></i>
                        Keamanan
                    </button>
                </div>
                <!-- ================= ACCOUNT ================= -->
                @include('setting.account_tab')
                <!-- ================= SECURITY ================= -->
                @include('setting.security_tab')
            </div>
        </div>
        @include('setting.resetFoto')
        @include('setting.logout')
    </div>
    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const modalId = sessionStorage.getItem('activeModal');
                if (modalId) {
                    const modal = document.getElementById(modalId);
                    if (modal) {
                        modal.classList.remove('hidden');
                        modal.classList.add('flex');
                    }
                }
            });
            window.activeTabFromServer = @json(session('active_tab'));
        </script>
    @endif
@endsection
