<div id="importModal" tabindex="-1" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-black/50">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg">
        <div class="flex items-center justify-between p-5 border-b">
            <h3 class="text-lg font-semibold">
                Import Data Pegawai
            </h3>
            <button data-modal-hide="importModal" class="text-gray-500 hover:text-red-600">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>
        <form action="{{ route('employee.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="p-6 space-y-5">
                <div>
                    <label class="font-medium">
                        Pilih File
                    </label>
                    <input type="file" name="file" required accept=".xlsx,.xls,.csv"
                        class="mt-2 block w-full border rounded-xl p-3">
                    <p class="text-xs text-gray-500 mt-2">
                        Format yang didukung:
                        Excel (.xlsx, .xls) dan CSV.
                    </p>
                </div>
                <div class="border rounded-xl p-4 bg-gray-50">
                    <p class="font-semibold mb-3">
                        Download Template
                    </p>
                    <div class="flex gap-3">
                        <a href="{{ route('employee.template', 'xlsx') }}"
                            class="flex-1 text-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg">
                            <i class="fa-solid fa-file-excel mr-2"></i>
                            Template Excel
                        </a>
                        <a href="{{ route('employee.template', 'csv') }}"
                            class="flex-1 text-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg">
                            <i class="fa-solid fa-file-csv mr-2"></i>
                            Template CSV
                        </a>
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-2 p-5 border-t">
                <button type="button" data-modal-hide="importModal" class="px-5 py-2 border rounded-xl">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl">
                    <i class="fa-solid fa-upload mr-2"></i>
                    Import
                </button>
            </div>
        </form>
    </div>
</div>
