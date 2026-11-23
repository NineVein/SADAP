<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CekProduksiTest extends TestCase
{
    use RefreshDatabase;

    /** Di lingkungan tes (bukan produksi) perintah harus berjalan sampai selesai dan melaporkan GAGAL. */
    public function test_perintah_berjalan_dan_menandai_lingkungan_non_produksi_gagal(): void
    {
        $this->artisan('sistem:cek-produksi')
            ->expectsOutputToContain('APP_ENV = production')
            ->assertFailed();
    }
}
