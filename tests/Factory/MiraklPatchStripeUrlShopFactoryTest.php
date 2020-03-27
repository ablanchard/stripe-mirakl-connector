<?php

namespace App\Tests\Controller;

use App\Factory\MiraklPatchStripeUrlShopFactory;
use PHPUnit\Framework\TestCase;

class MiraklPatchStripeUrlShopFactoryTest extends TestCase
{
    public function testBuildPatch()
    {
        $factory = new MiraklPatchStripeUrlShopFactory('stripe-key');
        $patch = $factory
            ->setMiraklShopId(1234)
            ->setStripeUrl('https://test')
            ->buildPatch();

        $this->assertEquals([
            'shop_id' => 1234,
            'shop_additional_fields' => [
                [
                    'code' => 'stripe-key',
                    'value' => 'https://test',
                ],
            ],
        ], $patch);
    }
}
