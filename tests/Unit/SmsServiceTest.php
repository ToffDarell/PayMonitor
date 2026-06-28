<?php

declare(strict_types=1);

use App\Services\SmsService;
use Illuminate\Support\Facades\Http;

test('send skips API call and returns false when credits are zero', function (): void {
    Http::fake([
        'unismsapi.com/api/account' => Http::response(['sms_credits' => 0], 200),
    ]);

    Http::assertNothingSent();

    $service = new SmsService;

    $result = $service->send('09171234567', 'Test message');

    expect($result)->toBeFalse();
    expect($service->getLastError())->toBe('No SMS credits remaining. Please top up your account.');
});

test('send proceeds when credits are sufficient', function (): void {
    Http::fake([
        'unismsapi.com/api/account' => Http::response(['sms_credits' => 50], 200),
        'unismsapi.com/api/sms' => Http::response([], 200),
    ]);

    $service = new SmsService;

    $result = $service->send('09171234567', 'Test message');

    expect($result)->toBeTrue();
    expect($service->getLastError())->toBeNull();
});

test('send returns false on API failure', function (): void {
    Http::fake([
        'unismsapi.com/api/account' => Http::response(['sms_credits' => 50], 200),
        'unismsapi.com/api/sms' => Http::response([], 500),
    ]);

    $service = new SmsService;

    $result = $service->send('09171234567', 'Test message');

    expect($result)->toBeFalse();
    expect($service->getLastError())->toContain('API responded with status');
});

test('send returns false for blank phone', function (): void {
    $service = new SmsService;

    expect($service->send('', 'Test'))->toBeFalse();
    expect($service->getLastError())->toBe('Phone number is empty.');
});

test('checkCredits returns int from API', function (): void {
    Http::fake([
        'unismsapi.com/api/account' => Http::response(['sms_credits' => 123], 200),
    ]);

    $service = new SmsService;

    expect($service->checkCredits())->toBe(123);
});

test('checkCredits returns null on failed API call', function (): void {
    Http::fake([
        'unismsapi.com/api/account' => Http::response([], 500),
    ]);

    $service = new SmsService;

    expect($service->checkCredits())->toBeNull();
});

test('checkCredits returns null on exception', function (): void {
    Http::fake([
        'unismsapi.com/api/account' => static fn (): never => throw new Exception('Connection refused'),
    ]);

    $service = new SmsService;

    expect($service->checkCredits())->toBeNull();
});

test('hasEnoughCredits returns true when enough credits exist', function (): void {
    Http::fake([
        'unismsapi.com/api/account' => Http::response(['sms_credits' => 10], 200),
    ]);

    $service = new SmsService;

    expect($service->hasEnoughCredits(5))->toBeTrue();
});

test('hasEnoughCredits returns false when insufficient credits', function (): void {
    Http::fake([
        'unismsapi.com/api/account' => Http::response(['sms_credits' => 2], 200),
    ]);

    $service = new SmsService;

    expect($service->hasEnoughCredits(5))->toBeFalse();
});

test('hasEnoughCredits returns true when API is unreachable', function (): void {
    Http::fake([
        'unismsapi.com/api/account' => Http::response([], 500),
    ]);

    $service = new SmsService;

    expect($service->hasEnoughCredits(5))->toBeTrue();
});

test('sendToMember returns false for member without phone', function (): void {
    $member = new App\Models\Member;
    $member->phone = null;

    $service = new SmsService;

    expect($service->sendToMember($member, 'Test'))->toBeFalse();
});
