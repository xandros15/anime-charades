<?php

use App\AnimeCharades;
use App\AnimeListManager;
use App\Database\SQLiteConnection;
use Slim\App;
use Slim\Exception\NotFoundException;

session_start();

require_once __DIR__ . '/../vendor/autoload.php';

$slim = new App();

$container = $slim->getContainer();

// Register Twig View helper
$container['view'] = function (\Slim\Container $container) {
    $view = new \Slim\Views\Twig(__DIR__ . '/../templates', [
//        'cache' => 'path/to/cache',
    ]);

    // Instantiate and add Slim specific extension
    $basePath = rtrim(str_ireplace('index.php', '', $container['request']->getUri()->getBasePath()), '/');
    $view->addExtension(new \Slim\Views\TwigExtension($container['router'], $basePath));

    return $view;
};

$slim->get('[/]', function (\Slim\Http\Request $request, \Slim\Http\Response $response) {

    $app = new AnimeCharades();
    $lists = AnimeListManager::loadAll();

    if (!$lists) {
        throw new NotFoundException($request, $response);
    }

    $app->generateList($lists);
    $anime = $app->roll();

    return $this->view->render($response, 'game.twig', [
        'anime' => $anime,
    ]);
});

$slim->get('/anime', function (\Slim\Http\Request $request, \Slim\Http\Response $response) {

    $connection = (new SQLiteConnection())->connect();
    $page = $request->getParam('p', 1);
    $search = $request->getParam('s', '');
    if ($search) {
        $offset = (int) ($page - 1) * 50;
        $stmt = $connection->prepare("SELECT * FROM anime WHERE `name` LIKE ? ORDER BY `name` LIMIT 50 OFFSET " . $offset);
        $stmt->execute(['%' . $search . '%']);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $offset = (int) ($page - 1) * 500;
        $items = $connection->query("SELECT * FROM anime ORDER BY name LIMIT 500 OFFSET " . $offset, PDO::FETCH_ASSOC);
    }

    return $this->view->render($response, 'list.twig', ['items' => $items, 'page' => $page]);
})->setName('anime.index');

$slim->post('/anime/update/{id:\d+}', function (\Slim\Http\Request $request, \Slim\Http\Response $response) {
    $id = (int) $request->getAttribute('id');
    $name = (string) trim($request->getParam('name', ''));
    if (!$name) {
        throw new NotFoundException($request, $response);
    }
    $connection = (new SQLiteConnection())->connect();
    $stmt = $connection->prepare("UPDATE anime SET main = ? WHERE id = ?");
    $stmt->execute([$name, $id]);

    return $this->view->render($response, '_delete-form.twig', ['id' => $id, 'main' => $name]);
})->setName('anime.update');

$slim->post('/anime/delete/{id:\d+}', function (\Slim\Http\Request $request, \Slim\Http\Response $response) {
    $id = (int) $request->getAttribute('id');
    $connection = (new SQLiteConnection())->connect();
    $stmt = $connection->prepare("UPDATE anime SET main = NULL WHERE id = ?");
    $stmt->execute([$id]);

    return $this->view->render($response, '_update-form.twig', ['id' => $id]);
})->setName('anime.delete');

$slim->get('/anime/hint', function (\Slim\Http\Request $request, \Slim\Http\Response $response) {

    if (!$name = $request->getParam('query')) {
        throw new NotFoundException($request, $response);
    }
    $connection = (new SQLiteConnection())->connect();
    $stmt = $connection->prepare("SELECT name FROM anime WHERE name LIKE ? ORDER BY name LIMIT 10");
    $stmt->execute(['%' . $name . '%']);
    $payload = $stmt->fetchAll(PDO::FETCH_COLUMN);

    return $response->withJson($payload);
})->setName('anime.hint');

$slim->get('/fetch', function (\Slim\Http\Request $request, \Slim\Http\Response $response) {
    $nicknames = [
    ];
    foreach ($nicknames as $nickname) {
        $mal = new \App\Mal();
        $list = $mal->fetch($nickname);
        AnimeListManager::save($list);
    }

    return $response->write('Done');
});


$slim->run();
