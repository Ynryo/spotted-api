<?php

namespace App\Tests;

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Dotenv\Dotenv;

abstract class AbstractKernelTestCase extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        if (!isset($_SERVER['APP_ENV'])) {
            $_SERVER['APP_ENV'] = 'test';
            $_ENV['APP_ENV'] = 'test';
            if (file_exists(dirname(__DIR__) . '/.env')) {
                (new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');
            }
        }

        return Kernel::class;
    }
}
