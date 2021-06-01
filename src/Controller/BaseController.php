<?php


namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class BaseController extends AbstractController
{
    public function postValidation($entity, $validator): ?JsonResponse
    {
        $errors = $validator->validate($entity);
        if (count($errors) > 0) {
            $formattedErrors = [];
            foreach ($errors as $error) {
                $formattedErrors[$error->getPropertyPath()] = [
                    'message' => sprintf('The property "%s" with value "%s" violated a requirement (%s)', $error->getPropertyPath(), $error->getInvalidValue(), $error->getMessage())
                ];
            }
            return $this->json($formattedErrors, 400);
        }
        return null;
    }

}