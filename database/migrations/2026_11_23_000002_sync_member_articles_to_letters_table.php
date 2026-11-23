<?php

use App\Models\Isf\Article;
use App\Models\Isf\Letter;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $memberArticles = Article::where('is_by_member', true)->whereNotNull('user_id')->get();
        foreach ($memberArticles as $article) {
            $user = User::find($article->user_id);
            Letter::updateOrCreate(
                ['slug' => $article->slug],
                [
                    'user_id' => $article->user_id,
                    'sender_name' => $article->author_name ?: ($user?->name ?? 'Member ISF'),
                    'sender_email' => $user?->email,
                    'title' => $article->title,
                    'category' => $article->category ?: 'kebijakan',
                    'subcategory' => $article->subcategory,
                    'summary' => $article->summary ?: '',
                    'body' => $article->body ?: '',
                    'author_role' => $article->author_role,
                    'author_avatar' => $article->author_avatar,
                    'reading_minutes' => $article->reading_minutes ?: 5,
                    'image_url' => $article->image_url,
                    'image_alt' => $article->image_alt,
                    'link_url' => $article->link_url ?: ('/artikel/' . ($article->category ?: 'kebijakan') . '/' . $article->slug),
                    'fact_blocks' => $article->fact_blocks,
                    'moderation_status' => $article->status === 'terbit' ? 'disetujui' : 'pending',
                    'status' => $article->status === 'terbit' ? 'terbit' : 'draft',
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
