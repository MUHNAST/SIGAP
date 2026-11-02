@foreach ($data as $pengguna)
    <!-- Modal Hapus Pengguna -->
    <div id="modalHapusUser{{ $pengguna->id }}"
        class="modal fixed inset-0 z-100 hidden items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
            <!-- HEADER -->
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-800">
                        Hapus Akun Pengguna
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Konfirmasi penghapusan data
                    </p>
                </div>
                <button type="button" onclick="closeAllModal()"
                    class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <!-- BODY -->
            <div class="px-6 py-8">
                <div
                    class="mx-auto mb-5 flex h-20 w-20 items-center justify-center rounded-full bg-red-100 text-3xl text-red-600">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <h3 class="mb-2 text-center text-lg font-bold text-slate-800">
                    Yakin ingin menghapus data ini?
                </h3>
                <p class="text-center text-slate-500">
                    Data pengguna
                    <span class="font-semibold text-slate-700">
                        {{ $pengguna->nama }}
                    </span>
                    akan dihapus secara permanen dan tidak dapat dikembalikan.
                </p>
            </div>
            @if ($errors->delete->has('error') && session('open_modal') == 'modalHapusUser' . $pengguna->id)
                <div class="mx-6 mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <i class="fa-solid fa-circle-exclamation mr-2"></i>
                    {{ $errors->delete->first('error') }}
                </div>
            @endif
            <!-- FOOTER -->
            <div class="flex justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4">
                <button type="button" onclick="closeAllModal()"
                    class="rounded-xl border border-slate-200 px-5 py-2.5 transition hover:bg-white">
                    Batal
                </button>
                <form action="{{ route('user.destroy', $pengguna) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="rounded-xl bg-red-600 px-5 py-2.5 text-white shadow-lg shadow-red-200 transition hover:bg-red-700">
                        <i class="fa-solid fa-trash mr-2"></i>
                        Hapus Data
                    </button>
                </form>
            </div>
        </div>
    </div>
@endforeach
