<?php

declare(strict_types=1);

use App\Application\Settings\SettingsInterface;
use App\Domain\PostalCode\Contracts\PostCodeRepositoryInterface;
use App\Infrastructure\Persistence\PostalCode\PostCodeRepository;
use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use App\Application\Middleware\AuthMiddleware;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        LoggerInterface::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);

            $loggerSettings = $settings->get('logger');
            $logger = new Logger($loggerSettings['name']);

            $processor = new UidProcessor();
            $logger->pushProcessor($processor);

            $handler = new StreamHandler($loggerSettings['path'], $loggerSettings['level']);
            $logger->pushHandler($handler);

            return $logger;
        },

        PDO::class => function () {
            return new PDO(
                sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                    getenv('DB_HOST'),
                    getenv('DB_PORT'),
                    getenv('DB_NAME'),
                ),
                getenv('DB_USER'),
                getenv('DB_PASS'),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        },

        PostCodeRepositoryInterface::class => function ($container) {
            $pdo = $container->get(PDO::class);
            return new PostCodeRepository($pdo);
        },

        AuthMiddleware::class => function () {
            return new AuthMiddleware(getenv('API_KEY'));
        },
    ]);
};
