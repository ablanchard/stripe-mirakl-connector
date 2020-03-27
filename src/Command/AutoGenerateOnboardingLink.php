<?php

namespace App\Command;

use App\Exception\InvalidArgumentException;
use App\Factory\MiraklPatchStripeUrlShopFactory;
use App\Factory\OnboardingAccountFactory;
use App\Utils\MiraklClient;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AutoGenerateOnboardingLink extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected static $defaultName = 'connector:sync:onboarding';
    protected const MAX_MIRAKL_BATCH_SIZE = 100;
    protected const DELAY_ARGUMENT_NAME = 'delay';

    /**
     * @var OnboardingAccountFactory
     */
    private $onboardingAccountFactory;

    /**
     * @var MiraklClient
     */
    private $miraklClient;

    /**
     * @var MiraklPatchStripeUrlShopFactory
     */
    private $patchFactory;

    /**
     * @var string
     */
    private $stripeUrlCustomFieldCode;

    public function __construct(
        OnboardingAccountFactory $onboardingAccountFactory,
        MiraklClient $miraklClient,
        MiraklPatchStripeUrlShopFactory $patchFactory,
        string $stripeUrlCustomFieldCode
    ) {
        $this->onboardingAccountFactory = $onboardingAccountFactory;
        $this->miraklClient = $miraklClient;
        $this->patchFactory = $patchFactory;
        $this->stripeUrlCustomFieldCode = $stripeUrlCustomFieldCode;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Generates Stripe Express onboarding links for new Mirakl Shops.')
            ->setHelp('This command will fetch Mirakl shops newly created, without any link in the configured custom field, will generate a link and update the Mirakl custom field.')
            ->addArgument(self::DELAY_ARGUMENT_NAME, InputArgument::OPTIONAL, 'Fetch shops updated in the last <delay> minutes. If empty, fetches all Mirakl Shops.')
    ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $output->writeln('Updating Stripe Express onboarding links for new Mirakl sellers');
        $delay = intval($input->getArgument(self::DELAY_ARGUMENT_NAME));

        if ($delay > 0) {
            $now = \DateTime::createFromFormat('U', (string) time());
            assert(false !== $now); // PHPStan helper
            $lastMapping = $now->modify(sprintf('-%d minutes', $delay));
        } else {
            $lastMapping = null;
        }

        $shopsToCheck = $this->miraklClient->fetchShops(null, $lastMapping, false);
        $output->writeln(sprintf('Found %d potentially new Mirakl Shops', count($shopsToCheck)));

        $shopsToUpdate = array_filter(array_map([$this, 'generateShopPatch'], $shopsToCheck));
        $output->writeln(sprintf('Updating %d new Mirakl Shops', count($shopsToUpdate)));

        if (count($shopsToUpdate) > 0) {
            $maxBatchSizeShops = array_chunk($shopsToUpdate, self::MAX_MIRAKL_BATCH_SIZE);
            foreach ($maxBatchSizeShops as $shopsChunk) {
                $this->miraklClient->patchShops($shopsChunk);
            }
        }

        return 0;
    }

    private function getStripeCustomFieldValue(array $additionalFields): ?string
    {
        foreach ($additionalFields as $field) {
            if ($field['code'] === $this->stripeUrlCustomFieldCode) {
                return $field['value'];
            }
        }

        return null;
    }

    private function generateShopPatch(array $miraklShop): ?array
    {
        $fieldValue = $this->getStripeCustomFieldValue($miraklShop['shop_additional_fields']);
        if (null !== $fieldValue) {
            // Link has already been generated, skip
            return null;
        }

        try {
            $stripeUrl = $this->onboardingAccountFactory->createFromMiraklShop($miraklShop);

            return $this->patchFactory
                ->setMiraklShopId($miraklShop['shop_id'])
                ->setStripeUrl($stripeUrl)
                ->buildPatch();
        } catch (InvalidArgumentException $e) {
            // Mirakl Shop is already linked to an existing Stripe account.
            $this->logger->error($e->getMessage(), [
                'miraklShopId' => $miraklShop['shop_id'],
            ]);
        }

        return null;
    }
}
