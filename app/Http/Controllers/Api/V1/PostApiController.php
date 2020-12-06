<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Goutte\Client;
use App\Models\CrawlPost;
use Illuminate\Support\Facades\Http;

class PostApiController extends Controller
{
    public function getLatestPost()
    {
        $client = new Client();

        $crawler = $client->request('GET', 'https://www.yardbarker.com/nba');

        $title = $crawler->filter('.rfi-body h2 a')->each(function ($node) {
            return $node->text();
        });

        if (CrawlPost::where('name', $title[0])->first()) {
            return response()->json([
                'success' => false,
                'msg' => 'no new post'
            ]);
        }

        $link = $crawler->selectLink($title[0])->link();

        $post_crawler = $client->click($link);

        $image = $post_crawler->filter('.flipboard-image')->attr('src');

        $description_arr = $post_crawler->filter('.article_chunk p')->each(function ($node) {
            return strip_tags($node->html());
        });

        $description_arr = array_slice($description_arr, 0, 3); // get first 3 lines

        $description = implode(' ', $description_arr);

        $created_post = CrawlPost::create([
            'name' => $title[0],
            'description' => $description,
        ]);

        // CrawlPost::find($created_post->id)
        //     ->addMediaFromUrl($image)
        //     ->toMediaCollection();

        return response()->json([
            'success' => true,
            'name' => $title[0],
            'description' => $description,
            'source' => 'YARDBARKER',
            'image' => $image
            // 'image' => CrawlPost::find($created_post->id)->getFirstMediaUrl()
        ]);
    }

    public function postWebHook()
    {
        Http::post(env('WEBHOOK_URL'), [
            $this->getLatestPost()
        ]);
    }
}
