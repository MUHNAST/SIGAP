<?php

use Illuminate\Support\Str;
if (! function_exists('format_nip')) {
    /**
     * Format NIP.
     *
     * Contoh:
     * 197401202006041002
     * menjadi
     * 19740120 200604 1 002
     */
    function format_nip(?string $nip): string
    {
        if (blank($nip)) {
            return '-';
        }
        $nip = Str::of($nip)
            ->replaceMatches('/\D+/', '')
            ->toString();
        if (strlen($nip) !== 18) {
            return $nip;
        }
        return sprintf(
            '%s %s %s %s',
            substr($nip, 0, 8),
            substr($nip, 8, 6),
            substr($nip, 14, 1),
            substr($nip, 15, 3)
        );
    }
}
if (! function_exists('format_rupiah')) {
    /**
     * Format nominal ke Rupiah.
     *
     * Contoh:
     * format_rupiah(5200000)
     * => Rp 5.200.000
     *
     * format_rupiah(5200000, false)
     * => 5.200.000
     */
    function format_rupiah(int|float|null $nominal, bool $symbol = true): string
    {
        $nominal ??= 0;
        $hasil = number_format($nominal, 0, ',', '.');
        return $symbol ? "Rp {$hasil}" : $hasil;
    }
}
if (! function_exists('terbilang')) {
    /**
     * Mengubah angka menjadi huruf.
     *
     * Contoh:
     * terbilang(1250)
     * => seribu dua ratus lima puluh
     */
    function terbilang(int|float $x): string
    {
        $x = abs((int) $x);
        $angka = [
            '',
            'satu',
            'dua',
            'tiga',
            'empat',
            'lima',
            'enam',
            'tujuh',
            'delapan',
            'sembilan',
            'sepuluh',
            'sebelas',
        ];
        if ($x < 12) {
            return ' ' . $angka[$x];
        }
        if ($x < 20) {
            return terbilang($x - 10) . ' belas';
        }
        if ($x < 100) {
            return terbilang((int) ($x / 10)) . ' puluh' . terbilang($x % 10);
        }
        if ($x < 200) {
            return ' seratus' . terbilang($x - 100);
        }
        if ($x < 1000) {
            return terbilang((int) ($x / 100)) . ' ratus' . terbilang($x % 100);
        }
        if ($x < 2000) {
            return ' seribu' . terbilang($x - 1000);
        }
        if ($x < 1000000) {
            return terbilang((int) ($x / 1000)) . ' ribu' . terbilang($x % 1000);
        }
        if ($x < 1000000000) {
            return terbilang((int) ($x / 1000000)) . ' juta' . terbilang($x % 1000000);
        }
        if ($x < 1000000000000) {
            return terbilang((int) ($x / 1000000000)) . ' milyar' . terbilang($x % 1000000000);
        }
        return terbilang((int) ($x / 1000000000000)) . ' triliun' . terbilang($x % 1000000000000);
    }
}
