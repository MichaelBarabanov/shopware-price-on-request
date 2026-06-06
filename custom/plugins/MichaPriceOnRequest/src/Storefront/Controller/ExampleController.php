<?php declare(strict_types=1);

namespace MichaPriceOnRequest\Storefront\Controller;

use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Storefront\Framework\Routing\StorefrontRouteScope;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: [PlatformRequest::ATTRIBUTE_ROUTE_SCOPE => [StorefrontRouteScope::ID]])]
class ExampleController extends StorefrontController
{
    public function __construct(
        private readonly SystemConfigService $systemConfigService,
        private readonly MailerInterface $mailer
    ) {}

    #[Route(
        path: '/micha-price-on-request/send',
        name: 'frontend.micha.price-on-request.send',
        methods: ['POST'],
        defaults: ['XmlHttpRequest' => true]
    )]
    public function send(Request $request, SalesChannelContext $context): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $name        = strip_tags($data['name'] ?? '');
        $email       = strip_tags($data['email'] ?? '');
        $message     = strip_tags($data['message'] ?? '');
        $productName = strip_tags($data['productName'] ?? '');
        $productId   = strip_tags($data['productId'] ?? '');

        if (!$name || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['success' => false, 'error' => 'Invalid input']);
        }

        $recipient = $this->systemConfigService->getString(
            'MichaPriceOnRequest.config.recipientEmail',
            $context->getSalesChannelId()
        );

        if (!$recipient) {
            return new JsonResponse(['success' => false, 'error' => 'No recipient configured']);
        }

        $body = "Neue Preisanfrage\n\n"
            . "Produkt: {$productName} (ID: {$productId})\n"
            . "Name: {$name}\n"
            . "E-Mail: {$email}\n"
            . "Nachricht: {$message}";

        $mail = (new Email())
            ->from($email)
            ->to($recipient)
            ->subject("Preisanfrage: {$productName}")
            ->text($body);

        try {
            $this->mailer->send($mail);
            return new JsonResponse(['success' => true]);
        } catch (\Throwable $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}