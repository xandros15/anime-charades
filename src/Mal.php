<?php


namespace App;


use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

class Mal
{
    const BASE_URL = 'https://myanimelist.net/malappinfo.php';

    private const STATUS_WATCHING = 1;
    private const STATUS_WATCHED = 2;

    private const ACCEPTED_STATUS = [
        self::STATUS_WATCHING,
        self::STATUS_WATCHED,
    ];

    /**
     * @param string $nick
     *
     * @return AnimeList
     */
    public function fetch(string $nick): AnimeList
    {
        $list = [];
        $client = new Client();

        $response = $client->request('GET', self::BASE_URL . '?' . http_build_query([
                'status' => 'all',
                'type' => 'anime',
                'u' => $nick,
            ]));

        $response->filter('anime')->each(function (Crawler $item) use (&$list) {
            if (in_array($item->filter('my_status')->text(), self::ACCEPTED_STATUS)) {
                $list[] = $item->filter('series_title')->text();
            }
        });

        return new AnimeList($nick, $list);
    }
}
