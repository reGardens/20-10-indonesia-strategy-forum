<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Sitemap\SitemapGenerator;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Generate sitemap.xml';

    public function handle()
    {
        // Create manual programmatic sitemap
        $sitemap = \Spatie\Sitemap\Sitemap::create();

        // 1. Static Pages
        $sitemap->add(\Spatie\Sitemap\Tags\Url::create('/')
            ->setPriority(1.0)
            ->setChangeFrequency(\Spatie\Sitemap\Tags\Url::CHANGE_FREQUENCY_DAILY));

        $sitemap->add(\Spatie\Sitemap\Tags\Url::create('/product')
            ->setPriority(0.8)
            ->setChangeFrequency(\Spatie\Sitemap\Tags\Url::CHANGE_FREQUENCY_WEEKLY));

        $sitemap->add(\Spatie\Sitemap\Tags\Url::create('/article')
            ->setPriority(0.8)
            ->setChangeFrequency(\Spatie\Sitemap\Tags\Url::CHANGE_FREQUENCY_WEEKLY));

        // 2. Active Products
        $products = \App\Models\Product::where('is_active', true)->get();
        foreach ($products as $product) {
            $sitemap->add(\Spatie\Sitemap\Tags\Url::create("/product/{$product->slug}")
                ->setPriority(0.8)
                ->setLastModificationDate($product->updated_at)
                ->setChangeFrequency(\Spatie\Sitemap\Tags\Url::CHANGE_FREQUENCY_WEEKLY));
        }

        // 3. Active Articles
        $articles = \App\Models\Article::where('is_active', true)->get();
        foreach ($articles as $article) {
            $sitemap->add(\Spatie\Sitemap\Tags\Url::create("/article/{$article->slug}")
                ->setPriority(0.7)
                ->setLastModificationDate($article->updated_at)
                ->setChangeFrequency(\Spatie\Sitemap\Tags\Url::CHANGE_FREQUENCY_WEEKLY));
        }

        // 4. Active Skin Problems (mapped to /{slug})
        $skinProblems = \App\Models\SkinProblem::where('is_active', true)->get();
        foreach ($skinProblems as $skin) {
            $sitemap->add(\Spatie\Sitemap\Tags\Url::create("/{$skin->slug}")
                ->setPriority(0.7)
                ->setLastModificationDate($skin->updated_at)
                ->setChangeFrequency(\Spatie\Sitemap\Tags\Url::CHANGE_FREQUENCY_WEEKLY));
        }

        // Write sitemap to file
        $sitemap->writeToFile(public_path('sitemap.xml'));

        $this->info('Sitemap generated programmatically with all URLs!');
    }
}