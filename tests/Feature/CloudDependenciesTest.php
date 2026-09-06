<?php

declare(strict_types=1);

use Aws\Sqs\SqsClient;
use Composer\InstalledVersions;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;

test('laravel cloud managed queue dependencies are installed', function (): void {
    // Laravel Cloud managed queues require aws/aws-sdk-php; the SQS client
    // class only exists when the package is installed.
    expect(class_exists(SqsClient::class))->toBeTrue();
});

test('laravel cloud object storage dependencies are installed', function (): void {
    // Laravel Cloud object storage requires league/flysystem-aws-s3-v3; the
    // S3 adapter class only exists when the package is installed.
    expect(class_exists(AwsS3V3Adapter::class))->toBeTrue();
});

test('laravel framework meets the managed queue minimum version', function (): void {
    // Managed queues require Laravel 13.19.0 or newer.
    $version = mb_ltrim(InstalledVersions::getPrettyVersion('laravel/framework'), 'v');

    expect($version)->not->toBeEmpty()
        ->and(version_compare($version, '13.19.0', '>='))->toBeTrue();
});
