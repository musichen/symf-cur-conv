<?php
namespace App\Controller\Api;

use App\Service\CurrencyConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CurrencyController extends AbstractController
{
    private CurrencyConverter $converter;

    public function __construct(CurrencyConverter $converter)
    {
        $this->converter = $converter;
    }

    #[Route('/api/convert', name: 'api_convert', methods: ['POST'])]
    public function convert(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $from = $data['from'] ?? null;
        $to = $data['to'] ?? null;
        $amount = $data['amount'] ?? null;

        if (empty($from) || empty($to) || !is_numeric($amount)) {
            return new JsonResponse(
                ['error' => 'Invalid input. Please provide valid "from", "to", and "amount" parameters.'],
                400
            );
        }
        $amount = (float) $amount;
        $convertedAmount = $this->converter->convert($from, $to, $amount);
        if ($convertedAmount === null) {
            return new JsonResponse(
                ['error' => 'Conversion not possible. Check currency codes or rates.'],
                400
            );
        }

        return new JsonResponse([
            'from' => $from,
            'to' => $to,
            'original_amount' => $amount,
            'converted_amount' => $convertedAmount,
        ]);
    }
}
