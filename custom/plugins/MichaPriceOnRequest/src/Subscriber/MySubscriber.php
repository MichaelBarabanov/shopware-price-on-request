<?php declare(strict_types=1);

namespace MichaPriceOnRequest\Subscriber;

use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MySubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly SystemConfigService $systemConfigService
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            ProductPageLoadedEvent::class => 'onProductPageLoaded'
        ];
    }

    public function onProductPageLoaded(ProductPageLoadedEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();
        $product = $event->getPage()->getProduct();

        $mode = $this->systemConfigService->getString(
            'MichaPriceOnRequest.config.mode',
            $salesChannelId
        ) ?: 'selected';

        $customFields = $product->getCustomFields() ?? [];
        $productActive = (bool) ($customFields['micha_por_active'] ?? false);

        $active = match($mode) {
            'all'      => true,
            'selected' => $productActive,
            default    => false,
        };

        $config = [
            'active'         => $active,
            'hidePrice'      => $active,
            'buttonLabel'    => $this->systemConfigService->getString(
                                    'MichaPriceOnRequest.config.buttonLabel',
                                    $salesChannelId
                                ) ?: 'Preis anfragen',
            'recipientEmail' => $this->systemConfigService->getString(
                                    'MichaPriceOnRequest.config.recipientEmail',
                                    $salesChannelId
                                ),
        ];

        $event->getPage()->addExtension('michaPriceOnRequest', new ArrayStruct($config));
    }
}