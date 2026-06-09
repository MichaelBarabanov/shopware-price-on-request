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
            'MichaPriceOnRequest.config.mode', $salesChannelId
        ) ?: 'selected';

        $customFields = $product->getCustomFields() ?? [];
        $productActive = (bool) ($customFields['micha_por_active'] ?? false);

        $active = match($mode) {
            'all'      => true,
            'selected' => $productActive,
            default    => false,
        };

        $fontSize = $this->systemConfigService->getInt(
            'MichaPriceOnRequest.config.buttonFontSize', $salesChannelId
        ) ?: 16;

        $rounded = $this->systemConfigService->getBool(
            'MichaPriceOnRequest.config.buttonRounded', $salesChannelId
        );

        $bgColor   = $this->systemConfigService->getString(
            'MichaPriceOnRequest.config.buttonBgColor', $salesChannelId
        ) ?: '#0d6efd';

        $textColor = $this->systemConfigService->getString(
            'MichaPriceOnRequest.config.buttonTextColor', $salesChannelId
        ) ?: '#ffffff';

        $config = [
            'active'         => $active,
            'hidePrice'      => $active,
            'hideAddToCart'  => $this->systemConfigService->getBool(
                                    'MichaPriceOnRequest.config.hideAddToCart', $salesChannelId
                                ),
            'buttonLabel'    => $this->systemConfigService->getString(
                                    'MichaPriceOnRequest.config.buttonLabel', $salesChannelId
                                ) ?: 'Preis anfragen',
            'recipientEmail' => $this->systemConfigService->getString(
                                    'MichaPriceOnRequest.config.recipientEmail', $salesChannelId
                                ),
            'buttonStyle'    => sprintf(
                'background-color:%s;color:%s;font-size:%dpx;border-radius:%s;',
                $bgColor,
                $textColor,
                $fontSize,
                $rounded ? '8px' : '0px'
            ),
        ];

        $event->getPage()->addExtension('michaPriceOnRequest', new ArrayStruct($config));
    }
}