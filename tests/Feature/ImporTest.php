<?php

namespace Tests\Feature;

use App\Models\Anak;
use App\Models\Kelurahan;
use App\Models\Panti;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImporTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Kelurahan $kelA;

    private Kelurahan $kelB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->admin = $this->buatUser(Role::ADMIN);
        $this->kelA = Kelurahan::create(['nama' => 'Kelurahan Contoh', 'kecamatan' => 'Tondano Barat', 'tipe' => 'kelurahan']);
        $this->kelB = Kelurahan::create(['nama' => 'Desa Lain', 'kecamatan' => 'Tondano Timur', 'tipe' => 'desa']);
    }

    private function buatUser(string $role, ?int $kelurahanId = null): User
    {
        return User::factory()->create([
            'role_id' => Role::where('name', $role)->value('id'),
            'kelurahan_id' => $kelurahanId,
            'is_active' => true,
        ]);
    }

    private function lks(string $nama, string $kategori, Kelurahan $kel): Panti
    {
        $p = new Panti(['nama' => $nama, 'kategori_lks' => $kategori, 'alamat' => 'Jl. Uji', 'kelurahan_id' => $kel->id, 'status' => 'aktif']);
        $p->save();

        return $p;
    }

    /** Unggah CSV lalu kembalikan token pratinjau. */
    private function unggah(User $user, string $jenis, string $csv): string
    {
        $res = $this->actingAs($user)->post('/impor/pratinjau', [
            'jenis' => $jenis,
            'file' => UploadedFile::fake()->createWithContent("{$jenis}.csv", $csv),
        ]);

        $res->assertSessionHasNoErrors()->assertRedirect();
        parse_str((string) parse_url($res->headers->get('Location'), PHP_URL_QUERY), $q);

        return $q['token'];
    }

    public function test_admin_dan_operator_dapat_mengakses_halaman_impor(): void
    {
        $this->actingAs($this->admin)->get('/impor')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Impor/Index')
                ->where('jenis', 'lks')
                ->has('kolom')
                ->has('opsi'));

        $operator = $this->buatUser(Role::OPERATOR, $this->kelA->id);
        $this->actingAs($operator)->get('/impor')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Impor/Index')
                ->where('isOperator', true));
    }

    public function test_templat_dapat_diunduh(): void
    {
        $this->actingAs($this->admin)->get('/impor/templat/lks')
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_supervisor_tidak_boleh_mengimpor(): void
    {
        $this->actingAs($this->buatUser(Role::SUPERVISOR))->get('/impor')->assertForbidden();
    }

    public function test_pratinjau_lks_memisahkan_baru_duplikat_dan_galat(): void
    {
        $this->lks('LKS Sudah Ada', 'anak', $this->kelA);

        $csv = "nama;kategori;alamat;kelurahan;kecamatan;tanggal_berdiri;status\n"
            ."LKS Baru Satu;Anak;Jl. A;Kelurahan Contoh;Tondano Barat;15/01/2020;aktif\n"
            ."LKS Salah;Hewan;Jl. B;Kelurahan Contoh;;;\n"
            ."LKS Sudah Ada;Anak;Jl. C;Kelurahan Contoh;;;\n";

        $token = $this->unggah($this->admin, 'lks', $csv);

        $this->actingAs($this->admin)->get("/impor?jenis=lks&token={$token}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Impor/Index')
                ->where('hasil.ringkasan.total', 3)
                ->where('hasil.ringkasan.baru', 1)
                ->where('hasil.ringkasan.duplikat', 1)
                ->where('hasil.ringkasan.galat', 1));

        // Pratinjau tidak menyimpan apa pun.
        $this->assertDatabaseMissing('pantis', ['nama' => 'LKS Baru Satu']);

        $this->actingAs($this->admin)->post('/impor/simpan', ['jenis' => 'lks', 'token' => $token])
            ->assertRedirect(route('panti.index'));

        $this->assertDatabaseHas('pantis', [
            'nama' => 'LKS Baru Satu', 'kategori_lks' => 'anak',
            'kelurahan_id' => $this->kelA->id, 'tanggal_berdiri' => '2020-01-15',
        ]);
        $this->assertDatabaseMissing('pantis', ['nama' => 'LKS Salah']);
        $this->assertSame(1, Panti::where('nama', 'LKS Sudah Ada')->count());
    }

    public function test_operator_hanya_boleh_mengimpor_lks_di_wilayahnya(): void
    {
        $operator = $this->buatUser(Role::OPERATOR, $this->kelA->id);

        $csv = "nama;kategori;alamat;kelurahan\n"
            ."LKS Op Satu;Anak;Jl. 1;\n"
            ."LKS Op Dua;Anak;Jl. 2;Desa Lain\n";

        $token = $this->unggah($operator, 'lks', $csv);

        $this->actingAs($operator)->get("/impor?jenis=lks&token={$token}")
            ->assertInertia(fn ($page) => $page
                ->where('hasil.ringkasan.baru', 1)
                ->where('hasil.ringkasan.galat', 1));

        $this->actingAs($operator)->post('/impor/simpan', ['jenis' => 'lks', 'token' => $token]);

        $this->assertDatabaseHas('pantis', ['nama' => 'LKS Op Satu', 'kelurahan_id' => $this->kelA->id]);
        $this->assertDatabaseMissing('pantis', ['nama' => 'LKS Op Dua']);
    }

    public function test_impor_klien_mengikuti_kategori_lks_dan_validasi_usia(): void
    {
        $this->lks('LKS Lansia Uji', 'lanjut_usia', $this->kelA);

        $csv = "nama_lks;nama;nik;jenis_kelamin;tanggal_lahir;latar_belakang;tingkat_ketergantungan\n"
            ."LKS Lansia Uji;Opa Satu;;L;01/01/1950;sebatang kara;sebagian\n"
            ."LKS Lansia Uji;Muda;;L;01/01/1995;terlantar;mandiri\n"
            ."LKS Lansia Uji;Opa Dua;1234567890123456;P;02/02/1948;dhuafa;total\n"
            ."LKS Lansia Uji;Opa Tiga;1234567890123456;L;03/03/1949;terlantar;mandiri\n";

        $token = $this->unggah($this->admin, 'klien', $csv);

        $this->actingAs($this->admin)->get("/impor?jenis=klien&token={$token}")
            ->assertInertia(fn ($page) => $page
                ->where('hasil.ringkasan.baru', 2)
                ->where('hasil.ringkasan.galat', 1)
                ->where('hasil.ringkasan.duplikat', 1));

        $this->actingAs($this->admin)->post('/impor/simpan', ['jenis' => 'klien', 'token' => $token])
            ->assertRedirect(route('anak.index'));

        $this->assertSame(2, Anak::count());
        $this->assertDatabaseHas('anaks', ['nama' => 'Opa Satu', 'kategori_klien' => 'lanjut_usia', 'kategori' => 'sebatang_kara']);
        $this->assertDatabaseMissing('anaks', ['nama' => 'Muda']);
    }

    public function test_klien_di_lks_luar_wilayah_operator_ditolak(): void
    {
        $this->lks('LKS Wilayah B', 'anak', $this->kelB);
        $operator = $this->buatUser(Role::OPERATOR, $this->kelA->id);

        $csv = "nama_lks;nama;jenis_kelamin;tanggal_lahir;latar_belakang\n"
            ."LKS Wilayah B;Anak Uji;L;01/01/2015;dhuafa\n";

        $token = $this->unggah($operator, 'klien', $csv);

        $this->actingAs($operator)->get("/impor?jenis=klien&token={$token}")
            ->assertInertia(fn ($page) => $page->where('hasil.ringkasan.galat', 1)->where('hasil.ringkasan.baru', 0));
    }

    public function test_kolom_wajib_yang_hilang_ditolak_dengan_pesan(): void
    {
        $this->actingAs($this->admin)->post('/impor/pratinjau', [
            'jenis' => 'lks',
            'file' => UploadedFile::fake()->createWithContent('x.csv', "nama;alamat\nA;B\n"),
        ])->assertSessionHasErrors('file');
    }

    public function test_token_pratinjau_tidak_bisa_dipakai_user_lain(): void
    {
        $csv = "nama;kategori;alamat;kelurahan;kecamatan\nLKS Rahasia;Anak;Jl. 1;Kelurahan Contoh;Tondano Barat\n";
        $token = $this->unggah($this->admin, 'lks', $csv);

        $lain = $this->buatUser(Role::ADMIN);
        $this->actingAs($lain)->post('/impor/simpan', ['jenis' => 'lks', 'token' => $token])
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('pantis', ['nama' => 'LKS Rahasia']);
    }
}
