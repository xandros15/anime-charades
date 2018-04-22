<?php

use App\AnimeCharades;
use App\AnimeCharadesManager;
use App\AnimeListManager;
use App\Database\Anime;
use App\Database\SQLiteConnection;
use App\Database\Translator;
use App\Mal;
use Slim\App;
use Slim\Container;
use Slim\Exception\NotFoundException;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Views\TwigExtension;

session_start();

require_once __DIR__ . '/../vendor/autoload.php';

$slim = new App();

$container = $slim->getContainer();

// Register Twig View helper
$container['view'] = function (Container $container) {
    $view = new \Slim\Views\Twig(__DIR__ . '/../templates', [
//        'cache' => 'path/to/cache',
    ]);

    // Instantiate and add Slim specific extension
    $basePath = rtrim(str_ireplace('index.php', '', $container['request']->getUri()->getBasePath()), '/');
    $view->addExtension(new TwigExtension($container['router'], $basePath));

    return $view;
};

$container['translator'] = function () {
    return new Translator(new SQLiteConnection());
};

$container['anime'] = function () {
    return new Anime(new SQLiteConnection());
};

$container['listManager'] = function () {
    return new AnimeListManager();
};

$container['charades'] = function () {
    return new AnimeCharadesManager();
};


$slim->group('/online', function () {
    /** @var $slim App */
    $slim = $this;

    $slim->post('/new', function (Request $request, Response $response) {
        $lists = [];
        foreach ($request->getParam('lists', []) as $listName) {
            $lists[] = $this->listManager->load($listName);
        }

        if (!$lists) {
            throw new NotFoundException($request, $response);
        }

        /** @var $game AnimeCharades */
        $game = $this->charades->createGame($lists, $this->translator);

        return $response->withRedirect($this->router->pathFor('online.join', ['game' => $game->getName()]));
    })->setName('online.new.post');

    $slim->get('/new', function (Request $request, Response $response) {
        $lists = $this->listManager->listAll();

        return $this->view->render($response, 'new-online.twig', [
            'lists' => $lists,
        ]);
    })->setName('online.new');

    $slim->group('/{game:\w+}', function () {
        /** @var $slim App */
        $slim = $this;

        $slim->get('[/]', function (Request $request, Response $response) {
            $gameName = $request->getAttribute('game');
            try {
                /** @var $game AnimeCharades */
                $game = $this->charades->load($gameName);
            } catch (\RuntimeException $e) {
                throw new NotFoundException($request, $response);
            }
            if (!$game->isStarted()) {
                return $this->view->render($response, 'game-message.twig', [
                    'message' => 'Gra jeszcze nie wystartowała',
                ]);
            } elseif (!$game->hasItems()) {
                return $this->view->render($response, 'game-message.twig', [
                    'message' => 'Skończyły się słowa',
                ]);
            } else {
                if ($_SESSION['game'][$request->getAttribute('game')]['nickname']
                    == $game->getCurrentPlayer()) {
                    $anime = $game->roll();

                    return $this->view->render($response, 'game-online.twig', [
                        'game' => $gameName,
                        'anime' => $anime,
                        'list' => $this->anime->related($anime->getName()),
                    ]);
                } else {

                    return $this->view->render($response, 'game-online-pass.twig', [
                        'charades' => $game,
                        'game' => $gameName,
                    ]);
                }
            }

        })->setName('online.game');


        $slim->get('/pass', function (Request $request, Response $response) {
            $gameName = $request->getAttribute('game');
            try {
                /** @var $game AnimeCharades */
                $game = $this->charades->load($gameName);
            } catch (\RuntimeException $e) {
                throw new NotFoundException($request, $response);
            }

            return $response->withJson([
                'isReload' => $game->getCurrentPlayer() == $_SESSION['game'][$request->getAttribute('game')]['nickname'],
                'currentPlayer' => $game->getCurrentPlayer(),
                'gameCount' => $game->count(),
            ]);
        })->setName('online.pass');

        $slim->get('/next', function (Request $request, Response $response) {
            $gameName = $request->getAttribute('game');
            try {
                /** @var $game AnimeCharades */
                $game = $this->charades->load($gameName);
                if ($game->getCurrentPlayer() != $_SESSION['game'][$request->getAttribute('game')]['nickname']) {
                    throw new \RuntimeException('Tylko ' . $game->getCurrentPlayer() . ' może oddać turę');
                } else {
                    $game->chosePlayer();
                }
            } catch (\RuntimeException $e) {
                return $this->view->render($response, 'game-message.twig', ['message' => $e->getMessage()]);
            }

            return $response->withRedirect($this->router->pathFor('online.game', ['game' => $gameName]));
        })->setName('online.next');

        $slim->get('/start', function (Request $request, Response $response) {
            $gameName = $request->getAttribute('game');
            try {
                /** @var $game AnimeCharades */
                $game = $this->charades->load($gameName);
                if (!$game->isStarted() && $game->getPlayers()) {
                    $game->start();
                    $game->chosePlayer();
                }
            } catch (\RuntimeException $e) {
                throw new NotFoundException($request, $response);
            }

            return $response->withRedirect($this->router->pathFor('online.game', ['game' => $gameName]));
        })->setName('online.start');

        $slim->post('/join', function (Request $request, Response $response) {
            $gameName = $request->getAttribute('game');
            if (isset($_SESSION['game'][$request->getAttribute('game')]['nickname'])) {
                return $response->withRedirect($this->router->pathFor('online.game', $gameName));
            }

            $nickname = $request->getParam('nickname');
            if (!$nickname) {
                throw new NotFoundException($request, $response);
            }
            try {
                /** @var $game AnimeCharades */
                $game = $this->charades->load($gameName);
                $game->addPlayer($nickname);
                $_SESSION['game'][$request->getAttribute('game')]['nickname'] = $nickname;
            } catch (\RuntimeException $e) {
                return $this->view->render($response, 'game-message.twig', ['message' => $e->getMessage()]);
            }

            return $response->withRedirect($this->router->pathFor('online.game', ['game' => $gameName]));
        })->setName('online.join.post');

        $slim->get('/join', function (Request $request, Response $response) {
            return $this->view->render($response, 'new-online-join.twig', ['game' => $request->getAttribute('game')]);
        })->setName('online.join');
    });
});

$slim->get('/restart', function (Request $request, Response $response) {
    $this->charades->delete('single');

    return $response->withRedirect($this->router->pathFor('single.new'));
})->setName('single.restart');

$slim->post('/new', function (Request $request, Response $response) {
    $lists = [];
    foreach ($request->getParam('lists') as $list) {
        $lists[] = $this->listManager->load($list);
    }

    if (!$lists) {
        return $this->view->render($response, 'game-message.twig', ['message' => 'Ale wybierz jakąś liste, proszę']);
    }

    $this->charades->createGame($lists, $this->translator, 'single');

    return $response->withRedirect($this->router->pathFor('single'));
})->setName('single.new.post');

$slim->get('/new', function (Request $request, Response $response) {
    $lists = $this->listManager->listAll();

    return $this->view->render($response, 'new-single.twig', [
        'lists' => $lists,
    ]);
})->setName('single.new');

$slim->get('[/]', function (Request $request, Response $response) {
    try {
        /** @var $charades AnimeCharades */
        $charades = $this->charades->load('single');
    } catch (\RuntimeException $e) {
        return $response->withRedirect($this->router->pathFor('single.new'));
    }

    if (!$charades->hasItems()) {
        return $this->view->render($response, 'game-message.twig', [
            'message' => 'Skończyły się słowa',
        ]);
    }

    $anime = $charades->roll();

    return $this->view->render($response, 'game.twig', [
        'anime' => $anime,
        'list' => $this->anime->related($anime->getName()),
    ]);
})->setName('single');

$slim->get('/anime', function (Request $request, Response $response) {
    $page = $request->getParam('p', 1);
    $search = $request->getParam('s', '');
    $items = $search ? $this->anime->search($search, $page) : $this->anime->list($page);

    return $this->view->render($response, 'list.twig', ['items' => $items, 'page' => $page]);
})->setName('anime.index');

$slim->post('/anime/update/{id:\d+}', function (Request $request, Response $response) {
    $id = (int) $request->getAttribute('id');
    $name = (string) trim($request->getParam('name', ''));
    if (!$name) {
        throw new NotFoundException($request, $response);
    }

    $this->anime->update($id, $name);

    return $this->view->render($response, '_delete-form.twig', ['id' => $id, 'main' => $name]);
})->setName('anime.update');

$slim->post('/anime/delete/{id:\d+}', function (Request $request, Response $response) {
    $id = (int) $request->getAttribute('id');
    $this->anime->delete($id);

    return $this->view->render($response, '_update-form.twig', ['id' => $id]);
})->setName('anime.delete');

$slim->get('/anime/hint', function (Request $request, Response $response) {
    if (!$name = $request->getParam('query')) {
        throw new NotFoundException($request, $response);
    }

    $payload = $this->anime->hint($name);

    return $response->withJson($payload);
})->setName('anime.hint');

$slim->post('/fetch', function (Request $request, Response $response) {
    $nickname = $request->getParam('nickname');
    if (!$nickname) {
        return $this->view->render($response, 'game-message.twig', ['message' => 'Może być wpisał jakąś liste']);
    }
    $mal = new Mal();
    $list = $mal->fetch($nickname);
    $this->listManager->save($list);
    $referer = $request->getServerParam('HTTP_REFERER');

    return $response->withRedirect($referer);
})->setName('fetch');


$slim->run();
