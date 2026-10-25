<?php

namespace App\Console\Commands;

use App\Models\Isf\Isu;
use App\Models\Isf\Tokoh;
use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Generate sitemap.xml for the public ISF pages';

    public function handle()
    {
        $sitemap = Sitemap::create();

        // 1. Static public pages
        $sitemap->add(Url::create('/')
            ->setPriority(1.0)
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY));
        $sitemap->add(Url::create('/beranda')
            ->setPriority(0.9)
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY));

        // 2. Published tokoh profiles
        foreach (Tokoh::query()->terbit()->get(['slug', 'updated_at']) as $tokoh) {
            $sitemap->add(Url::create("/tokoh/{$tokoh->slug}")
                ->setPriority(0.7)
                ->setLastModificationDate($tokoh->updated_at)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY));
        }

        // 3. Published isu (Anatomi Isu Mingguan)
        foreach (Isu::query()->terbit()->get(['slug', 'updated_at']) as $isu) {
            $sitemap->add(Url::create("/isu/{$isu->slug}")
                ->setPriority(0.8)
                ->setLastModificationDate($isu->updated_at)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY));
        }

        $sitemap->writeToFile(public_path('sitemap.xml'));

        $this->info('Sitemap generated with the published ISF URLs.');
    }
}
