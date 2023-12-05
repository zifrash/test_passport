<?php

namespace App\Controller;

use App\Entity\Passport;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

class PassportController extends AbstractController
{
    #[Route(
        path: '/passport/check',
        methods: 'POST',
        format: 'json'
    )]
    public function check(Request $request, ValidatorInterface $validator, EntityManagerInterface $entityManager): Response
    {
        $series = $request->getPayload()->get('series');
        $number = $request->getPayload()->get('number');

        $stringConstraint = new Assert\Type(
            type: 'string',
            message: 'The value {{ value }} is not a valid {{ type }}.'
        );
        $seriesError = $validator->validate($series, $stringConstraint);
        $numberError = $validator->validate($number, $stringConstraint);

        if ($seriesError->count() > 0 || $numberError->count() > 0) {
            return new JsonResponse(
                data: ['message' => ($seriesError[0] ?? $numberError[0])->getMessage()],
                status: Response::HTTP_BAD_REQUEST
            );
        }

        return new JsonResponse([
            'valid' => !empty(
                $entityManager->getRepository(Passport::class)->findBy([
                    'series' => $series,
                    'number' => $number
                ])
            )
        ]);
    }
}