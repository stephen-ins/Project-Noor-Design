<?php

namespace App\Twig;

use App\Entity\Orders;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    private $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('order_number', [$this, 'formatOrderNumber']),
        ];
    }

    public function formatOrderNumber(Orders $order): string
    {
        $dateFormat = $order->getDateCommande()->format('dmY');
        $orderId = $order->getId();

        // Récupérer le format mais enlever les balises HTML éventuelles
        $format = $this->parameterBag->get('order_number_format');
        $format = strip_tags($format);

        return sprintf(
            $format,
            $dateFormat,
            $orderId
        );
    }
}
