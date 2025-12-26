<?php

namespace App\EventSubscriber;

use App\Middleware\JwtManager;
use App\Security\Authenticated;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;


final class JwtAuthSubscriber implements EventSubscriberInterface
{
    public function __construct(private JwtManager $jwt, private LoggerInterface $logger) { }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::CONTROLLER => 'OnController'];
    }

    public function onController(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        $this->logger->debug('Request Headers', [
            'method' => $request->getMethod(),
            'uri' => $request->getUri(),
            'headers'=> $request->headers->all(),
            'Body' => $request->getContent()
        ]);

        if ($request->isMethod('OPTIONS'))
        { 
            return;
        }
        $needAuth = false;
        $controller = $event->getController();
        if (!\is_array($controller))
        {
            $ref = new \ReflectionClass($controller);
            $needAuth = \count($ref->getAttributes(Authenticated::class)) > 0;
            
            $this->logger->info('the array controller is empty, and the getAttributes is ');
        }
        else
        {
            [$controllerObj, $method] = $controller;
            $ref = new \ReflectionClass($controllerObj);
            $refMethod = $ref->getMethod($method);
            $needAuth = \count($ref->getAttributes(Authenticated::class)) > 0
                    || \count($refMethod->getAttributes(Authenticated::class)) > 0;
        }

        if (!$needAuth)
        {
            return;
        }

        $token = $this->getBearerToken($request);
        if ($token === null)
        {
            $event->setController(  fn()  => new JsonResponse(['error' => 'missing Authorization header'],
                                                                    Response::HTTP_UNAUTHORIZED));
             return;
        }
        if (!$this->jwt->validateToken($token))
        {
            $event->setController(  fn()  => new JsonResponse(['error' => 'Invalid or expired token'],
                                                                    Response::HTTP_UNAUTHORIZED));
             return;
        }

        $claims = $this->jwt->decodeToken($token) ?? [];
        $this->logger->debug('jwt_email'.'   '.($claims['jwt_email'] ?? 'empty'));
        $request->attributes->set('jwt_claims', $claims);
        $request->attributes->set('jwt_usr_id', $claims['jwt_usr_id'] ?? null);
        $request->attributes->set('jwt_email', $claims['jwt_email'] ?? null);
    }

    private function getBearerToken(Request $request): ?string
    {
       foreach ($request->headers->all() as $name => $values) {
            $this->logger->debug('Header: ' . $name, ['value' => implode(', ', $values)]);
        }

        $header = $request->headers->get('authorization');
        if ($header === null)
        { 
            $this->logger->error('The header is missing ');
        }
        else {
        }

        if ($header === null || !str_starts_with($header, 'Bearer'))
        {
            return null;
        }
        return trim(substr($header, 7));
    }
}
