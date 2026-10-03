<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

if (!class_exists('Redis')) {
    class Redis
    {
        public function get(string $key): mixed { return false; }
        public function set(string $key, mixed $value, mixed $options = null): mixed { return true; }
        /**
         * @param array<string> $keys
         * @return array<mixed>|false
         */
        public function mGet(array $keys): array|false { return false; }
    }
}
