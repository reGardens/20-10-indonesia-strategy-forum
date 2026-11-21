<?php

use App\Models\Isf\Article;
use App\Models\Isf\Letter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $approvedLetters = Letter::where('moderation_status', 'disetujui')->get();
        foreach ($approvedLetters as $letter) {
            $slug = $letter->slug ?: Str::slug($letter->title);
            if (! $slug) {
                $slug = 'artikel-member-'.$letter->id;
            }

            Article::updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $letter->title,
                    'category' => $letter->category ?: 'opini',
                    'subcategory' => $letter->subcategory ?: 'surat-pembaca',
                    'published_at' => $letter->published_at ?? $letter->created_at ?? now(),
                    'summary' => $letter->summary ?: Str::limit(strip_tags($letter->body), 200),
                    'body' => $letter->body,
                    'author_name' => $letter->author_name ?: $letter->sender_name,
                    'author_role' => $letter->author_role,
                    'author_avatar' => $letter->author_avatar,
                    'reading_minutes' => $letter->reading_minutes ?: 5,
                    'image_url' => $letter->image_url,
                    'image_alt' => $letter->image_alt,
                    'link_url' => $letter->link_url,
                    'fact_blocks' => $letter->fact_blocks,
                    'status' => 'terbit',
                    'is_by_member' => true,
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};

