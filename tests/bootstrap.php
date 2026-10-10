<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

if (!class_exists(Redis::class)) {
    class Redis
    {
        public function connect(string $host, int $port): bool
        {
            return true;
        }

        /**
         * @param array<string, mixed> $options
         */
        public function set(string $key, string $value, array $options = []): bool
        {
            return true;
        }

        public function get(string $key): string|false
        {
            return false;
        }
    }
}
