<?php declare(strict_types=1);

namespace MichaPriceOnRequest;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetCollection;
use Shopware\Core\System\CustomField\CustomFieldTypes;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MichaPriceOnRequest extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        if ($this->container === null) {
            return;
        }

        $this->createCustomField($installContext->getContext(), $this->container);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

        if ($this->container === null) {
            return;
        }

        $this->removeCustomField($uninstallContext->getContext(), $this->container);
    }

    public function activate(ActivateContext $activateContext): void {}
    public function deactivate(DeactivateContext $deactivateContext): void {}
    public function update(UpdateContext $updateContext): void {}

    private function createCustomField(Context $context, ContainerInterface $container): void
    {
        /** @var EntityRepository<CustomFieldSetCollection> $customFieldSetRepository */
        $customFieldSetRepository = $container->get('custom_field_set.repository');

        $customFieldSetRepository->upsert([
            [
                'name'         => 'micha_price_on_request',
                'config' => [
                    'label' => [
                        'de-DE' => 'Preis auf Anfrage',
                        'en-GB' => 'Price on Request',
                    ]
                ],
                'relations'    => [
                    ['entityName' => 'product']
                ],
                'customFields' => [
                    [
                        'name'   => 'micha_por_active',
                        'type'   => CustomFieldTypes::BOOL,
                        'config' => [
                            'label' => [
                                'de-DE' => 'Preis auf Anfrage aktiv',
                                'en-GB' => 'Price on Request active',
                            ],
                            'customFieldPosition' => 1,
                        ],
                    ],
                ],
            ]
        ], $context);
    }

    private function removeCustomField(Context $context, ContainerInterface $container): void
    {
        /** @var EntityRepository<CustomFieldSetCollection> $customFieldSetRepository */
        $customFieldSetRepository = $container->get('custom_field_set.repository');

        $criteria = new \Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria();
        $criteria->addFilter(
            new \Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter(
                'name', 'micha_price_on_request'
            )
        );

        $result = $customFieldSetRepository->searchIds($criteria, $context);

        if ($result->getTotal() > 0) {
            $ids = array_map(fn($id) => ['id' => $id], $result->getIds());
            $customFieldSetRepository->delete($ids, $context);
        }
    }
}