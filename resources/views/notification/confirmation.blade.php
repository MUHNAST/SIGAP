@if ($statistics['belumDibaca'] > 0)
    <div id="confirmReadAllModal" tabindex="-1" aria-hidden="true"
        class="hidden fixed inset-0 z-[100] flex justify-center items-center w-full h-full bg-black/40">
        <div class="relative w-full max-w-md p-4">
            <div class="relative bg-white rounded-2xl shadow">
                {{-- Header --}}
                <div class="flex items-center justify-between p-5 border-b">
                    <h3 class="text-lg font-semibold text-slate-800">
                        Konfirmasi
                    </h3>
                    <button type="button" data-modal-hide="confirmReadAllModal"
                        class="text-slate-400 hover:text-slate-700">
                        <i class="fa-solid fa-xmark text-xl"></i>
                    </button>
                </div>
                {{-- Body --}}
                <div class="p-6 text-center">
                    <div class="mx-auto flex items-center justify-center w-16 h-16 rounded-full bg-blue-100 mb-4">
                        <i class="fa-solid fa-check-double text-2xl text-blue-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">
                        Tandai semua notifikasi?
                    </h3>
                    <p class="text-slate-500">
                        Semua notifikasi yang belum dibaca akan diubah menjadi
                        <strong>Sudah Dibaca</strong>.
                    </p>
                </div>
                {{-- Footer --}}
                <div class="flex justify-end gap-3 p-5 border-t">
                    <button type="button" data-modal-hide="confirmReadAllModal"
                        class="px-5 py-2.5 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-100">
                        Batal
                    </button>
                    <form action="{{ route('notification.read-all') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white">
                            Ya, Tandai Semua
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endif
