<?php declare(strict_types=1);

namespace MichaPriceOnRequest\Storefront\Controller;

use MichaPriceOnRequest\Service\RateLimiter;
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
        private readonly MailerInterface $mailer,
        private readonly RateLimiter $rateLimiter
    ) {}

    #[Route(
        path: '/micha-price-on-request/send',
        name: 'frontend.micha.price-on-request.send',
        methods: ['POST'],
        defaults: ['XmlHttpRequest' => true]
    )]
    public function send(Request $request, SalesChannelContext $context): JsonResponse
    {
        $salesChannelId = $context->getSalesChannelId();
        $data = json_decode($request->getContent(), true);

        if (!empty($data['website'])) {
            return new JsonResponse(['success' => true]);
        }

        $spamProtectionEnabled = $this->systemConfigService->getBool(
            'MichaPriceOnRequest.config.spamProtectionEnabled',
            $salesChannelId
        );

        if ($spamProtectionEnabled) {
            $maxRequests = $this->systemConfigService->getInt(
                'MichaPriceOnRequest.config.spamMaxRequests',
                $salesChannelId
            ) ?: 3;

            if ($maxRequests < 1) {
                $maxRequests = 1;
            }

            $ip = $request->getClientIp() ?? 'unknown';

            if (!$this->rateLimiter->isAllowed($ip, $maxRequests)) {
                return new JsonResponse([
                    'success' => false,
                    'error'   => 'Zu viele Anfragen. Bitte versuche es später erneut.'
                ], 429);
            }
        }

        $name        = strip_tags($data['name'] ?? '');
        $email       = strip_tags($data['email'] ?? '');
        $message     = strip_tags($data['message'] ?? '');
        $productName = strip_tags($data['productName'] ?? '');
        $productId   = strip_tags($data['productId'] ?? '');

        if (!$name || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['success' => false, 'error' => 'Ungültige Eingabe']);
        }

        $recipient = $this->systemConfigService->getString(
            'MichaPriceOnRequest.config.recipientEmail',
            $salesChannelId
        );

        if (!$recipient) {
            return new JsonResponse(['success' => false, 'error' => 'Kein Empfänger konfiguriert']);
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
        } catch (\Throwable $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()]);
        }

        $confirmationEnabled = $this->systemConfigService->getBool(
            'MichaPriceOnRequest.config.confirmationEmailEnabled',
            $salesChannelId
        );

        if ($confirmationEnabled) {
            $subject = $this->systemConfigService->getString(
                'MichaPriceOnRequest.config.confirmationEmailSubject',
                $salesChannelId
            ) ?: 'Ihre Preisanfrage ist eingegangen';

            $confirmationText = $this->systemConfigService->getString(
                'MichaPriceOnRequest.config.confirmationEmailText',
                $salesChannelId
            ) ?: 'Vielen Dank für Ihre Anfrage. Wir melden uns so schnell wie möglich bei Ihnen.';

            $confirmationBody = "{$confirmationText}\n\n"
                . "Ihre Anfrage:\n"
                . "Produkt: {$productName}\n"
                . "Nachricht: {$message}";

            $confirmationMail = (new Email())
                ->from($recipient)
                ->to($email)
                ->subject($subject)
                ->text($confirmationBody);

            try {
                $this->mailer->send($confirmationMail);
            } catch (\Throwable $e) {
                // Bestätigungsmail-Fehler ignorieren
            }
        }

        return new JsonResponse(['success' => true]);
    }
}