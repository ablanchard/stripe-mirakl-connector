<?php

namespace App\Factory;

class MiraklPatchStripeUrlShopFactory
{
    /**
     * @var string
     */
    private $stripeUrlCustomFieldCode;

    /**
     * @var int
     */
    private $miraklShopId;

    /**
     * @var string
     */
    private $stripeUrl;

    public function __construct(string $stripeUrlCustomFieldCode)
    {
        $this->stripeUrlCustomFieldCode = $stripeUrlCustomFieldCode;
    }

    public function setMiraklShopId(int $miraklShopId): self
    {
        $this->miraklShopId = $miraklShopId;

        return $this;
    }

    public function setStripeUrl(string $stripeUrl): self
    {
        $this->stripeUrl = $stripeUrl;

        return $this;
    }

    public function buildPatch()
    {
        return [
            'shop_id' => $this->miraklShopId,
            'shop_additional_fields' => [
                [
                    'code' => $this->stripeUrlCustomFieldCode,
                    'value' => $this->stripeUrl,
                ],
            ],
        ];
    }
}
