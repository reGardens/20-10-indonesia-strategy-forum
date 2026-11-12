<?php

namespace Database\Seeders;

use App\Models\Isf\Article;
use App\Models\Isf\Chart;
use App\Models\Isf\Infographic;
use App\Models\Isf\Setting;
use App\Models\Isf\Tokoh;
use App\Services\Isf\IsfCacheService;
use Illuminate\Database\Seeder;

/**
 * Melengkapi konten contoh ISF agar halaman publik terlihat seperti situs nyata:
 * gambar untuk artikel, infografik, tokoh dan thumbnail media, ditambah beberapa
 * artikel Politik, infografik, dan satu insight baru.
 *
 * Aman dijalankan berulang dan tidak merusak data yang dibuat lewat CMS:
 * - Record baru dibuat dengan firstOrCreate (slug/judul unik); record yang sudah ada tidak diubah.
 * - Gambar hanya diisi bila kolomnya masih kosong, jadi gambar yang diunggah lewat CMS tidak ditimpa.
 * - Tidak ada data yang dihapus. Pengaturan iklan tidak disentuh.
 *
 * Gambar memakai foto Unsplash (lisensi Unsplash, boleh dipakai gratis), dirujuk lewat URL.
 */
class IsfRealisticContentSeeder extends Seeder
{
    private static function img(string $photoId, int $width = 1200): string
    {
        return "https://images.unsplash.com/photo-{$photoId}?q=80&w={$width}&auto=format&fit=crop";
    }

    public function run(): void
    {
        $this->seedArticleImages();
        $this->seedPolitikArticles();
        $this->seedInfographics();
        $this->seedTokohPhoto();
        $this->seedInsight();
        $this->seedMediaThumbnails();

        IsfCacheService::clearAll();
        // clearAll() tidak menghapus cache per-tokoh pada driver file, jadi bersihkan satu per satu.
        foreach (Tokoh::query()->pluck('slug') as $slug) {
            IsfCacheService::clearTokoh($slug);
        }
    }

    /** Gambar untuk artikel contoh yang sudah ada (slug dari IsfContentSeeder). */
    private function seedArticleImages(): void
    {
        $images = [
            // Hero / Top News: KUHP baru dan pelaku usaha
            'kuhp-baru-pelaku-usaha' => self::img('1589829545856-d10d557cf95f', 1600),
            // Terkini
            'dpr-jadwalkan-rapat-kerja-lanjutan-ruu-perampasan-aset' => self::img('1529107386315-e1a2ed48a620', 800),
            'pemerintah-siapkan-aturan-turunan-kuhp-untuk-pidana-korporasi' => self::img('1450101499163-c8848c66ca85', 800),
            'inflasi-september-terkendali-bi-tahan-suku-bunga-acuan' => self::img('1611974789855-9c2a0a7236a3', 800),
            'koalisi-partai-bahas-peta-jalan-legislasi-akhir-tahun' => self::img('1540575467063-178a50c2df87', 1200),
            'kementerian-terbitkan-pedoman-kepatuhan-bagi-umkm' => self::img('1554224155-6726b3ff858f', 800),
            'asosiasi-pengusaha-minta-masa-transisi-sanksi-administratif' => self::img('1542744173-8e7e53415bb0', 800),
            // Isu terpopuler
            'ruu-perampasan-aset-masuk-pembahasan-tingkat-i' => self::img('1505664194779-8beaceb93744', 800),
            'pedoman-kepatuhan-umkm-apa-saja-kewajibannya' => self::img('1454165804606-c3d57bc86b40', 800),
            'arah-kebijakan-suku-bunga-hingga-akhir-2026' => self::img('1590283603385-17ffb3a7f29f', 800),
            'peta-jalan-legislasi-prioritas-dpr' => self::img('1517048676732-d65bc937f952', 800),
        ];

        foreach ($images as $slug => $url) {
            $this->fillIfEmpty(Article::where('slug', $slug)->first(), 'image_url', $url);
        }
    }

    /** Beranda > Politik & Parlemen butuh 4 artikel kategori politik. */
    private function seedPolitikArticles(): void
    {
        $articles = [
            [
                'slug' => 'komisi-ii-buka-konsultasi-publik-revisi-uu-pemilu',
                'title' => 'Komisi II buka konsultasi publik revisi UU Pemilu',
                'summary' => 'Uji publik draf revisi menghimpun masukan akademisi, penyelenggara pemilu, dan masyarakat sipil sebelum pembahasan tingkat I.',
                'published_at' => '2026-11-04',
                'author_name' => 'Tim Riset Politik ISF',
                'author_role' => 'Analis Parlemen',
                'reading_minutes' => 4,
                'image_url' => self::img('1556761175-5973dc0f32e7', 800),
                'image_alt' => 'Diskusi publik pembahasan revisi undang-undang pemilu',
            ],
            [
                'slug' => 'fraksi-dpr-sepakati-jadwal-pembahasan-rapbn-2027',
                'title' => 'Fraksi DPR sepakati jadwal pembahasan RAPBN 2027',
                'summary' => 'Badan Musyawarah menetapkan tahapan pembahasan anggaran, termasuk rapat kerja dengan mitra komisi dan pandangan fraksi.',
                'published_at' => '2026-11-02',
                'author_name' => 'Tim Riset Politik ISF',
                'author_role' => 'Analis Anggaran',
                'reading_minutes' => 5,
                'image_url' => self::img('1517245386807-bb43f82c33c4', 800),
                'image_alt' => 'Rapat pembahasan jadwal anggaran negara',
            ],
            [
                'slug' => 'survei-kepercayaan-publik-terhadap-parlemen-naik',
                'title' => 'Survei: kepercayaan publik terhadap parlemen naik tipis',
                'summary' => 'Hasil survei triwulan menunjukkan kenaikan kepercayaan publik, didorong keterbukaan rapat dan percepatan legislasi prioritas.',
                'published_at' => '2026-10-30',
                'author_name' => 'Tim Data ISF',
                'author_role' => 'Analis Survei',
                'reading_minutes' => 3,
                'image_url' => self::img('1460925895917-afdab827c52f', 800),
                'image_alt' => 'Dasbor data hasil survei opini publik',
            ],
        ];

        foreach ($articles as $data) {
            $article = Article::firstOrCreate(['slug' => $data['slug']], $data + [
                'category' => 'politik',
                'link_url' => null,
                'is_hero' => false,
                'show_in_terkini' => false,
                'is_popular' => false,
                'popular_rank' => null,
                'status' => 'terbit',
            ]);
            $this->fillIfEmpty($article, 'image_url', $data['image_url']);
        }
    }

    /** Gambar infografik yang sudah ada + 2 infografik baru. */
    private function seedInfographics(): void
    {
        $images = [
            'update-linimasa-berlakunya-kuhp-nasional' => self::img('1551288049-bebda4e38f71', 1200),
            'peta-tahapan-ruu-prioritas-2026' => self::img('1543286386-713bdd548da4', 1200),
            'sektor-usaha-paling-terdampak-regulasi-baru' => self::img('1581091226825-a6a2a5aee158', 800),
            'siapa-berwenang-dalam-penegakan-pidana-korporasi' => self::img('1434030216411-0b793f4b4173', 800),
        ];
        foreach ($images as $slug => $url) {
            $this->fillIfEmpty(Infographic::where('slug', $slug)->first(), 'image_url', $url);
        }

        $new = [
            [
                'slug' => 'peta-transisi-energi-bauran-ebt-2026',
                'title' => 'Peta transisi energi: bauran EBT 2026',
                'category' => 'kebijakan',
                'published_at' => '2026-11-05',
                'image_alt' => 'Infografik bauran energi baru terbarukan nasional 2026',
                'image_url' => self::img('1509391366360-2e959784a276', 1200),
            ],
            [
                'slug' => 'tren-investasi-asing-per-sektor-2026',
                'title' => 'Tren investasi asing per sektor 2026',
                'category' => 'ekonomi',
                'published_at' => '2026-10-28',
                'image_alt' => 'Infografik tren penanaman modal asing per sektor usaha',
                'image_url' => self::img('1518186285589-2f7649de83e0', 1200),
            ],
        ];
        foreach ($new as $data) {
            $info = Infographic::firstOrCreate(['slug' => $data['slug']], $data + ['status' => 'terbit']);
            $this->fillIfEmpty($info, 'image_url', $data['image_url']);
        }
    }

    /** Foto profil tokoh (sosok berjas, wajah tidak ditampilkan). */
    private function seedTokohPhoto(): void
    {
        $this->fillIfEmpty(
            Tokoh::where('slug', 'budi-santoso')->first(),
            'photo_url',
            self::img('1507679799987-c73779587ccf', 800),
        );
    }

    /** Satu insight tambahan untuk slider ISF Insight. */
    private function seedInsight(): void
    {
        Chart::firstOrCreate(['title' => 'Kepastian regulasi menurut pelaku usaha'], [
            'description' => 'Persentase responden yang menilai regulasi sektornya sudah memberi kepastian berusaha (data contoh, Oktober 2026).',
            'author_name' => 'Tim Data ISF',
            'author_role' => 'Analis Survei Kebijakan',
            'unit' => '%',
            'data' => [
                ['label' => 'Keuangan', 'value' => 58],
                ['label' => 'Energi', 'value' => 44],
                ['label' => 'Manufaktur', 'value' => 39],
                ['label' => 'Digital', 'value' => 31],
                ['label' => 'Ritel', 'value' => 27],
            ],
            'source_title' => 'Data contoh redaksi ISF',
            'source_url' => null,
            'is_beranda' => false,
            'order' => 3,
            'status' => 'terbit',
        ]);
    }

    /** Thumbnail media sorotan (Video) yang masih kosong; field lain tidak diubah. */
    private function seedMediaThumbnails(): void
    {
        $beranda = Setting::get('beranda');
        $media = $beranda['mediaSorotan'] ?? null;
        if (! is_array($media) || $media === []) {
            return;
        }

        $thumbs = [
            self::img('1563986768609-322da13575f3', 1200),
            self::img('1466611653911-95081537e5b7', 800),
            self::img('1524178232363-1fb2b075b655', 800),
            self::img('1488590528505-98d2b5aba04b', 800),
        ];

        $changed = false;
        foreach ($media as $i => $item) {
            if (is_array($item) && empty($item['imageUrl'])) {
                $media[$i]['imageUrl'] = $thumbs[$i % count($thumbs)];
                $changed = true;
            }
        }

        if ($changed) {
            $beranda['mediaSorotan'] = $media;
            Setting::put('beranda', $beranda);
        }
    }

    private function fillIfEmpty(?object $model, string $field, string $value): void
    {
        if ($model && empty($model->{$field})) {
            $model->{$field} = $value;
            $model->save();
        }
    }
}
