<div class="max-w-6xl mx-auto" style="font-family: Arial, Helvetica, sans-serif;">
    <!--
      SPESIFIKASI DOKUMEN:
      - Font: Arial (Locked via inline style)
      - Ukuran: 11pt (text-[15px])
      - Spasi: 1,5 (leading-[1.5])
      - Ukuran Kertas: F4 / Folio (215mm x 330mm)
      - Margin: Atas 25mm, Kanan 25mm, Bawah 30mm, Kiri 30mm
    -->
    <div class="bg-white shadow-2xl mx-auto text-black" style="width:210mm; box-sizing:border-box;">
        {{-- ================= HEADER ================= --}}
        <div class="px-[20mm] pt-[10mm]">
            <div class="relative w-full">
                {{-- Logo --}}
                <div class="absolute left-[10mm] top-0 w-[14mm]">
                    <img src="{{ asset('images/pekalongan.png') }}" alt="Logo"
                        style="width:18mm;height:22mm;object-fit:contain;">
                </div>
                {{-- Tulisan Kop --}}
                <div class="text-center pl-[26mm] pr-[14mm]">
                    <h3 class="text-[17px] uppercase tracking-wider leading-tight m-0">
                        PEMERINTAH KOTA PEKALONGAN
                    </h3>
                    <h1 class="text-[24px] font-bold uppercase tracking-wide leading-tight m-0">
                        KECAMATAN PEKALONGAN TIMUR
                    </h1>
                    <p class="text-[13px] m-0">
                        Jalan Tondano Nomor 19 Telepon (0285) 422328 Pekalongan
                    </p>
                    <p class="text-[13px] m-0">
                        Kode Pos 51122 Email : kecamatanpekalongantimur@gmail.com
                    </p>
                </div>
            </div>
            {{-- Garis Kop --}}
            <div class="border-b-[4px] border-double border-black mb-2"></div>
            <div class="w-full text-[13px] leading-[1.5]">
                {{-- Tanggal --}}
                <div class="flex justify-end mb-2 ">
                    <div class="w-[72mm] text-left pl-3">
                        <span contenteditable="true" class="editable outline-none hover:bg-red-400 focus:bg-blue-50"
                            data-field="tanggal_surat">
                            Pekalongan,
                            {{ optional($surat->tanggal_sk_baru)->translatedFormat('d F Y') ?? now()->translatedFormat('d F Y') }}
                        </span>
                    </div>
                </div>
                {{-- Nomor dan Tujuan --}}
                <div class="flex justify-between items-start">
                    {{-- Kolom Kiri --}}
                    <div class="w-[110mm]">
                        <table class="w-full table-fixed">
                            <tr class="align-top">
                                <td class="w-[14mm]">Nomor</td>
                                <td class="w-[5mm] text-center">:</td>
                                <td>
                                    <span contenteditable="true"
                                        class="editable outline-none hover:bg-red-400 focus:bg-blue-50"
                                        data-field="nomor_surat_baru">
                                        {{ $surat->nomor_sk_baru }}
                                    </span>
                                </td>
                            </tr>
                            <tr class="align-top">
                                <td>Sifat</td>
                                <td class="text-center">:</td>
                                <td>Amat Segera</td>
                            </tr>
                            <tr class="align-top">
                                <td>Lampiran</td>
                                <td class="text-center">:</td>
                                <td>-</td>
                            </tr>
                            <tr class="align-top">
                                <td>Perihal</td>
                                <td class="text-center">:</td>
                                <td>
                                    Kenaikan Gaji Berkala An.
                                    {{ $employee->jenis_kelamin === 'Perempuan' ? 'Sdri.' : 'Sdr.' }}<br>
                                    {{ $employee->nama}}
                                </td>
                            </tr>
                        </table>
                    </div>
                    {{-- Kolom Kanan --}}
                    <div class="w-[80mm] pl-3">
                        <p class="m-0">Kepada</p>
                        <p class="m-0">
                            Yth. Kepala BPKAD Kota Pekalongan
                        </p>
                        <input type="hidden" name="tujuan" id="tujuan">
                        </p>
                        <p class="m-0 mt-4">di –</p>
                        <p class="pl-10 mt-4 underline uppercase">PEKALONGAN</p>
                    </div>
                </div>
            </div>
        </div>
        {{-- ================= ISI SURAT ================= --}}
        {{-- <div class="px-[30mm] pr-[25mm] pt-[4mm] pb-[30mm] text-[13px] leading-[1.5]"> --}}
        <div class="px-[30mm] pr-[14mm] pt-[2mm] text-[13px] leading-[1.5]">
            {{-- ================= KONTEN UTAMA SURAT ================= --}}
            <div class="w-full pl-10">
                {{-- Teks Pembuka --}}
                <div class="mb-2">
                    Dengan ini diberitahukan bahwa berhubung dengan telah dipenuhinya masa kerja dan syarat-syarat
                    lainnya
                    kepada :
                </div>
                {{-- ================= DATA PEGAWAI ================= --}}
                <div class="mb-2">
                    <table class="w-full table-fixed text-[13px] leading-[1.5]">
                        <tr class="align-top">
                            <td class="w-6 text-left ">1.</td>
                            <td class="w-45 text-left ">Nama</td>
                            <td class="w-4 text-center ">:</td>
                            <td class="uppercase">{{ $employee->nama}}</td>
                        </tr>
                        <tr class="align-top">
                            <td class="py-0.5">2.</td>
                            <td class="py-0.5">NIP</td>
                            <td class="text-center py-0.5">:</td>
                            <td class="py-0.5">{{ format_nip($employee->nip) }}</td>
                        </tr>
                        <tr class="align-top">
                            <td class="py-0.5">3.</td>
                            <td class="py-0.5">Tempat / Tanggal Lahir</td>
                            <td class="text-center py-0.5">:</td>
                            <td class="py-0.5">
                                {{ \Illuminate\Support\Str::title(strtolower($employee->birthplace?->nama?? 'Pekalongan')) }},
                                {{ \Carbon\Carbon::parse($employee->tanggal_lahir)->translatedFormat('d F Y') }}
                            </td>
                        </tr>
                        <tr class="align-top">
                            <td class="py-0.5">4.</td>
                            <td class="py-0.5">Pangkat / Golongan</td>
                            <td class="text-center py-0.5">:</td>
                            <td class="py-0.5">{{ $employee->rank?->nama?? '-' }}</td>
                            {{-- ({{ $employee->rank?->kode ?? 'III/a' }}) --}}
                        </tr>
                        <tr class="align-top">
                            <td class="py-0.5">5.</td>
                            <td class="py-0.5">Jabatan</td>
                            <td class="text-center py-0.5">:</td>
                            <td class="py-0.5">
                                {{ $employee->position?->nama?? '-' }}
                            </td>
                        </tr>
                        <tr class="align-top">
                            <td class="py-0.5">6.</td>
                            <td class="py-0.5">Satuan Kerja</td>
                            <td class="text-center py-0.5">:</td>
                            <td class="py-0.5">Kecamatan Pekalongan Timur</td>
                        </tr>
                        <tr class="align-top">
                            <td class="py-0.5">7.</td>
                            <td class="py-0.5">Gaji Pokok Lama</td>
                            <td class="py-0.5 text-center">:</td>
                            <td class="py-0.5">
                                <span data-field="gaji_pokok_lama">
                                    {{ format_rupiah($surat->gaji_pokok_lama) }}
                                </span>
                            </td>
                        </tr>
                        {{-- Detail Sub Gaji Lama --}}
                        <tr class="align-top">
                            <td></td>
                            <td colspan="3" class="text-[13px]">
                                (atas dasar Skp. Terakhir Gaji / Pangkat yang ditetapkan)
                            </td>
                        </tr>
                        <tr class="align-top">
                            <td></td>
                            <td class="py-0.5">a. Oleh Pejabat</td>
                            <td class="text-center py-0.5">:</td>
                            <td class="py-0.5">
                                <span data-field="oleh_pejabat">
                                    Camat Pekalongan Timur
                                </span>
                            </td>
                        </tr>
                        <tr class="align-top">
                            <td></td>
                            <td class="py-0.5">b. Tanggal</td>
                            <td class="text-center py-0.5">:</td>
                            <td class="py-0.5">
                                <span contenteditable="true"
                                    class="editable outline-none hover:bg-red-400 focus:bg-blue-50"
                                    data-field="tanggal_det_lama">
                                    {{ $surat->tanggal_sk_lama ? \Carbon\Carbon::parse($surat->tanggal_sk_lama)->translatedFormat('d F Y') : '-' }}
                                </span>
                            </td>
                        </tr>
                        <tr class="align-top">
                            <td></td>
                            <td class="py-0.5 pl-4">Nomor</td>
                            <td class="text-center py-0.5">:</td>
                            <td class="py-0.5">
                                <span contenteditable="true" data-field="nomor_surat_lama"
                                    class="editable outline-none hover:bg-red-400 focus:bg-blue-50">
                                    {{ $surat->nomor_sk_lama ?? '-' }}
                                    {{-- {{ dd($surat) }} --}}
                                </span>
                            </td>
                        </tr>
                        {{-- @dd($surat->pejabat_lama, $surat->tanggal_sk_sebelumnya, $surat->nomor_sk_sebelumnya) --}}
                        <tr class="align-top">
                            <td></td>
                            <td class="py-0.5">c. Tanggal. Mulai Berlakunya <span class="pl-4">Gaji
                                    tersebut</span>
                            </td>
                            <td class="text-center py-0.5">:</td>
                            <td class="py-0.5">
                                {{ optional($surat->tmt_kgb_lama)->translatedFormat('d F Y') }}
                            </td>
                        </tr>
                        <tr class="align-top">
                            <td></td>
                            <td class="py-0.5">e. Masa Kerja Golongan pada <span class="pl-3">Tanggal
                                    tersebut</span></td>
                            <td class="text-center py-0.5">:</td>
                            <td class="py-0.5">
                                <span data-field="masa_kerja_golongan">
                                    {{ $surat->tahun_masa_kerja_lama }}
                                    Tahun
                                    {{ sprintf('%02d', $surat->bulan_masa_kerja_lama) }}
                                    Bulan
                                </span>
                            </td>
                        </tr>
                        {{-- Spacing Kalimat Penghubung --}}
                        <tr class="align-top">
                            <td></td>
                            <td colspan="3" class="pl-3 font-normal">
                                Diberikan Kenaikan Gaji Berkala sehingga memperoleh :
                            </td>
                        </tr>
                        {{-- Detail Gaji Baru --}}
                        <tr class="align-top">
                            <td class="py-0.5">8.</td>
                            <td class="py-0.5">Gaji Pokok Baru</td>
                            <td class="py-0.5 text-center">:</td>
                            <td class="py-0.5">
                                <div class="flex flex-wrap items-start gap-1">
                                    <span data-field="gaji_pokok_baru">
                                        {{ format_rupiah($surat->gaji_pokok_baru) }}
                                        {{-- {{ dd($surat->gaji_pokok_baru) }} --}}
                                    </span>
                                    <span data-field="terbilang_gaji_pokok_baru">
                                        ({{ ucwords(trim(terbilang($surat->gaji_pokok_baru))) }} Rupiah)
                                    </span>
                                </div>
                            </td>
                        </tr>
                        <tr class="align-top">
                            <td class="py-0.5">9.</td>
                            <td class="py-0.5">Berdasarkan Masa Kerja</td>
                            <td class="text-center py-0.5">:</td>
                            <td class="py-0.5">
                                <span data-field="masa_kerja_baru">
                                    {{ $surat->tahun_masa_kerja_baru }} Tahun
                                    {{ sprintf('%02d', $surat->bulan_masa_kerja_baru) }} Bulan </span>
                            </td>
                        </tr>
                        <tr class="align-top">
                            <td class="py-0.5">10.</td>
                            <td class="py-0.5">Dalam Golongan</td>
                            <td class="text-center py-0.5">:</td>
                            <td class="py-0.5">{{ $employee->rank?->kode ?? '-' }}</td>
                        </tr>
                        <tr class="align-top">
                            <td class="py-0.5">11.</td>
                            <td class="py-0.5">Mulai Tanggal</td>
                            <td class="text-center py-0.5">:</td>
                            <td class="py-0.5">
                                {{ optional($surat->tmt_kgb_baru)->translatedFormat('d F Y') }}
                            </td>
                        </tr>
                        {{-- {{ dd($surat) }} --}}
                    </table>
                </div>
                {{-- Teks Penutup --}}
                <div class="mb-2 ">
                    Diharap agar sesuai dengan
                    <span contenteditable="true" data-field="peraturan"
                        class="editable outline-none hover:bg-red-400 focus:bg-blue-50">
                        {{ $surat->dasar_hukum_kgb }}
                    </span>
                    kepada Pegawai tersebut dapat dibayarkan penghasilannya berdasarkan Gaji Pokok yang baru.
                </div>
            </div>
        </div>
        {{-- ================= TANDA TANGAN ================= --}}
        <div class="flex justify-end mt-2 mb-2">
            <div class="w-80 text-center text-[14px] leading-[1.5]">
                <p class="m-0 uppercase">
                    <span data-field="ttd_pejabat">
                        {{-- {{ optional($camat->position)->nama?? 'Camat Pekalongan Timur' }} --}}
                        CAMAT PEKALONGAN TIMUR
                    </span>
                </p>
                <p class="m-0 uppercase">KOTA PEKALONGAN</p>
                <div class="h-15"></div>
                <p class="m-0 font-bold uppercase underline tracking-wide">
                    <span data-field="nama_camat">
                        {{-- {{ filled($camat->nama) ? $camat->nama: 'NAMA CAMAT, S.Sos' }} --}}
                        {{ $camat?->nama ?: 'NAMA CAMAT, S.Sos' }}
                    </span>
                </p>
                <p class="m-0 text-[13px]">
                    <span data-field="jabatan_camat">
                        {{ optional($camat->rank)->nama
                            ? optional($camat->rank)->nama. ' (' . optional($camat->rank)->kode . ')'
                            : '-' }}
                    </span>
                </p>
                <p class="m-0 text-[13px]">
                    NIP.
                    <span data-field="nip_camat">
                        {{ $camat?->nip ? format_nip($camat->nip) : '-' }}
                    </span>
                </p>
            </div>
        </div>
        {{-- ================= TEMBUSAN ================= --}}
        <div class="text-[13px] leading-[1.5] pt-2 ml-[18mm]">
            <p class="m-0 text-black uppercase tracking-normal text-[14px]">
                TEMBUSAN :
            </p>
            <ol class="list-decimal pl-5 mt-1  text-gray-900 w-[90mm] border-b-1">
                <li>Inspektur Kota Pekalongan;</li>
                <li>Kepala Badan Kepegawaian dan Pengembangan Sumber Daya Manusia Daerah Kota Pekalongan;</li>
                <li>Kepala Dinkominfo Kota Pekalongan;</li>
                <li>Kepala Kantor Cab. PT. Taspen (PERSERO) di Pekalongan;</li>
                <li>Saudara yang bersangkutan;</li>
                <li>Arsip.</li>
            </ol>
        </div>
    </div>
</div>
