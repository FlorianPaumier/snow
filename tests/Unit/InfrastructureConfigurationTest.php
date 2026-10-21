<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Symfony\Component\Yaml\Yaml;

it('keeps development infrastructure accessible only from localhost', function (): void {
    $compose = Yaml::parseFile(base_path('docker-compose.yml'));
    foreach ($compose['services'] as $service) {
        foreach ($service['ports'] ?? [] as $port) {
            expect($port)->toStartWith('127.0.0.1:');
        }
    }
});

it('requires browser tests before the deployment stage', function (): void {
    $pipeline = Yaml::parseFile(base_path('.gitlab-ci.yml'));
    expect($pipeline['browser']['extends'])->toBe('.base')
        ->and($pipeline['.base']['stage'])->toBe('test')
        ->and($pipeline['browser']['allow_failure'] ?? false)->toBeFalse()
        ->and($pipeline['browser']['script'])->toContain('bunx playwright install --with-deps chromium')
        ->and($pipeline['browser']['script'])->toContain('php artisan test --compact --testsuite=Browser --exclude-group=visual')
        ->and($pipeline['stages'])->toBe(['test', 'deploy']);
});

it('assigns type-aware lint to the standard GitLab-hosted amd64 runner', function (): void {
    $pipeline = Yaml::parseFile(base_path('.gitlab-ci.yml'));

    expect($pipeline['lint']['tags'])->toBe(['saas-linux-small-amd64'])
        ->and($pipeline['lint']['script'])->toContain('GOMAXPROCS=2 bun run test:lint')
        ->and($pipeline['lint']['allow_failure'] ?? false)->toBeFalse();
});

it('connects host Laravel mail to the local Mailpit SMTP port', function (): void {
    $compose = Yaml::parseFile(base_path('docker-compose.yml'));
    $environment = Dotenv::parse(file_get_contents(base_path('.env.example')));
    expect($environment['MAIL_MAILER'])->toBe('smtp')
        ->and($environment['MAIL_HOST'])->toBe('127.0.0.1')
        ->and($environment['MAIL_PORT'])->toBe('2525')
        ->and($compose['services']['mailpit']['ports'])->toContain(
            $environment['MAIL_HOST'].':'.$environment['MAIL_PORT'].':1025',
            '127.0.0.1:8026:8025',
        );
});
