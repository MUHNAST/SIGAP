<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureReferencedRanksExist();

        Schema::table('promotion_histories', function (Blueprint $table) {
            $table->dropForeign(['id_pangkat_baru']);
            $table->dropForeign(['id_pangkat_lama']);
            $table->foreign('id_pangkat_baru')
                ->references('id')
                ->on('ranks')
                ->cascadeOnDelete();
            $table->foreign('id_pangkat_lama')
                ->references('id')
                ->on('ranks')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        $this->ensureReferencedUsersExist();

        Schema::table('promotion_histories', function (Blueprint $table) {
            $table->dropForeign(['id_pangkat_baru']);
            $table->dropForeign(['id_pangkat_lama']);
            $table->foreign('id_pangkat_baru')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            $table->foreign('id_pangkat_lama')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    private function ensureReferencedRanksExist(): void
    {
        foreach (['id_pangkat_baru', 'id_pangkat_lama'] as $column) {
            $hasMissingRank = DB::table('promotion_histories')
                ->whereNotExists(function ($query) use ($column) {
                    $query->selectRaw('1')
                        ->from('ranks')
                        ->whereColumn('ranks.id', 'promotion_histories.'.$column);
                })
                ->exists();

            if ($hasMissingRank) {
                throw new RuntimeException(
                    "Cannot point promotion_histories.{$column} to ranks: existing values have no matching rank."
                );
            }
        }
    }

    private function ensureReferencedUsersExist(): void
    {
        foreach (['id_pangkat_baru', 'id_pangkat_lama'] as $column) {
            $hasMissingUser = DB::table('promotion_histories')
                ->whereNotExists(function ($query) use ($column) {
                    $query->selectRaw('1')
                        ->from('users')
                        ->whereColumn('users.id', 'promotion_histories.'.$column);
                })
                ->exists();

            if ($hasMissingUser) {
                throw new RuntimeException(
                    "Cannot restore the users foreign key for promotion_histories.{$column}: existing values have no matching user."
                );
            }
        }
    }
};
