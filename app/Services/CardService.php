<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\CardRepository;

class CardService
{
    public function __construct(
        private CardRepository $card_repository
    ) {}

    /**
     * Menampilkan data agenda berdasarkan jenis.
     */
    public function getUpcoming(
        string $jenis,
        ?User $user = null
    ): array {
        $information =
            $this->card_repository
                ->getTypeInformation($jenis);

        $employees =
            $this->card_repository
                ->getUpcoming(
                    $jenis,
                    $user
                );

        return [
            'jenis' => $information['jenis'],
            'title' => $information['title'],
            'deskripsi' => $information['deskripsi'],
            'shortTitle' => $information['short_title'],
            'dateField' => $information['date_field'],
            'dateLabel' => $information['date_label'],
            'period' => $information['period'],
            'icon' => $information['icon'],
            'color' => $information['color'],
            'data' => $employees,
            'total' => $employees->count(),
        ];
    }
}
