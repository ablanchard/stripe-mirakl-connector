<?php

namespace App\Factory;

class MiraklPatchStripeIdShopFactory
{
    /**
     * @var string
     */
    private $stripeIdCustomFieldCode;

    /**
     * @var int
     */
    private $miraklShopId;

    /**
     * @var string
     */
    private $stripeId;

    public function __construct(string $stripeIdCustomFieldCode)
    {
        $this->stripeIdCustomFieldCode = $stripeIdCustomFieldCode;
    }

    public function setMiraklShopId(int $miraklShopId): self
    {
        $this->miraklShopId = $miraklShopId;

        return $this;
    }

    public function setStripeId(string $stripeId): self
    {
        $this->stripeId = $stripeId;

        return $this;
    }

    public function buildPatch()
    {
        return [
            'shop_id' => $this->miraklShopId,
            'shop_additional_fields' => [
                [
                    'code' => $this->stripeIdCustomFieldCode,
                    'value' => $this->stripeId,
                ],
            ],
        ];
    }
}
