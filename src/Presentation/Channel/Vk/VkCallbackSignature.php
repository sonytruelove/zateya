<?php

declare(strict_types=1);

namespace Src\Presentation\Channel\Vk;

/**
 * Проверка подписи запроса VK mini app (launch params) по секрету приложения.
 * Алгоритм: HMAC-SHA256 от отсортированных параметров «vk_*», сравнение с «sign».
 */
final class VkCallbackSignature
{
    public function __construct(private readonly string $appSecret)
    {
    }

    /**
     * @param array<string, string> $params
     */
    public function isValid(array $params, string $providedSign): bool
    {
        if ($this->appSecret === '' || $providedSign === '') {
            return false;
        }

        $vkParams = array_filter($params, static fn (string $key): bool => str_starts_with($key, 'vk_'), ARRAY_FILTER_USE_KEY);
        ksort($vkParams);

        $expected = rtrim(strtr(base64_encode(hash_hmac('sha256', http_build_query($vkParams), $this->appSecret, true)), '+/', '-_'), '=');

        return hash_equals($expected, $providedSign);
    }
}
