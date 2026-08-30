<?php

declare(strict_types=1);

namespace Tests\Unit\Presentation;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Src\Presentation\Channel\Vk\VkCallbackSignature;

final class VkCallbackSignatureTest extends TestCase
{
    private const SECRET = 'app-secret-value';

    #[Test]
    public function it_accepts_a_correctly_signed_set_of_launch_parameters(): void
    {
        $params = ['vk_user_id' => '42', 'vk_app_id' => '7', 'sign' => 'ignored', 'other' => 'x'];
        $sign = $this->sign(['vk_app_id' => '7', 'vk_user_id' => '42']);

        self::assertTrue((new VkCallbackSignature(self::SECRET))->isValid($params, $sign));
    }

    #[Test]
    public function it_rejects_a_tampered_parameter(): void
    {
        $sign = $this->sign(['vk_app_id' => '7', 'vk_user_id' => '42']);
        $tampered = ['vk_user_id' => '43', 'vk_app_id' => '7'];

        self::assertFalse((new VkCallbackSignature(self::SECRET))->isValid($tampered, $sign));
    }

    #[Test]
    public function it_rejects_when_the_secret_is_empty(): void
    {
        self::assertFalse((new VkCallbackSignature(''))->isValid(['vk_user_id' => '1'], 'anything'));
    }

    /**
     * @param array<string, string> $sortedVkParams
     */
    private function sign(array $sortedVkParams): string
    {
        return rtrim(strtr(base64_encode(
            hash_hmac('sha256', http_build_query($sortedVkParams), self::SECRET, true),
        ), '+/', '-_'), '=');
    }
}
