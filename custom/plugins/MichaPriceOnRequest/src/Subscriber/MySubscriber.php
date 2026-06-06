<?php declare(strict_types=1);

namespace MichaPriceOnRequest\Subscriber;

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

        $config = [
            'active'         => true,
            'hidePrice'      => $this->systemConfigService->getBool(
                                    'MichaPriceOnRequest.config.hidePrice',
                                    $salesChannelId
                                ),
            'buttonLabel'    => $this->systemConfigService->getString(
                                    'MichaPriceOnRequest.config.buttonLabel',
                                    $salesChannelId
                                ) ?: 'Preis anfragen',
            'recipientEmail' => $this->systemConfigService->getString(
                                    'MichaPriceOnRequest.config.recipientEmail',
                                    $salesChannelId
                                ),
        ];

        $event->getPage()->addExtension('michaPriceOnRequest', new \Shopware\Core\Framework\Struct\ArrayStruct($config));
    }
}