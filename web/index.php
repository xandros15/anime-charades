<?php

use App\AnimeCharades;
use App\AnimeList;
use Slim\App;

session_start();

require_once __DIR__ . '/../vendor/autoload.php';


$slim = new App();
$slim->get('[/]', function (\Slim\Http\Request $request, \Slim\Http\Response $response) {

    dump($_SESSION);
    $app = new AnimeCharades();
    $lists = [];

    foreach (glob(__DIR__ . '/../storage/*.json') as $item) {
        if ($list = AnimeList::load(basename($item, '.json'))) {
            $lists[] = $list;
        }
    }

    if (!$lists) {
        throw new \Slim\Exception\NotFoundException($request, $response);
    }

    $app->generateList($lists);
    $anime = $app->roll();
    $users = implode(', ', $anime->getUsers());
    $payload = "<div>
        <a target=\"_blank\" href=\"https://anidb.net/perl-bin/animedb.pl?adb.search={$anime->getName()}&show=search&do.search=search\">{$anime->getName()}</a>
        <p>Users: {$users}</p> 
    </div>";

    return $response->write($payload);
});

$slim->get('/fetch', function (\Slim\Http\Request $request, \Slim\Http\Response $response) {
    $nicknames = [
    ];
    foreach ($nicknames as $nickname) {
        $mal = new \App\Mal();
        $list = $mal->fetch($nickname);
        $list->save();
    }

    return $response->write('Done');
});


$slim->run();

