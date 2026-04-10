<?php

namespace App\Controller;

use App\Entity\Libro;
use App\Repository\LibroRepository;


use Doctrine\ORM\EntityManagerInterface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\Json;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api', name: 'app_api_')]
final class ApiLibroController extends AbstractController
{

    const MIN_LENGTH_TITULO = 2;
    const MAX_LENGTH_TITULO = 10;

    const MIN_LENGTH_DESC = 10;
    const MAX_LENGTH_DESC = 255;

    #[Route('/libros', name: 'libros', methods: ['GET'])]
    public function index(LibroRepository $repo): JsonResponse
    {
        return $this->json($repo->findAll());
    }

    //Obtener un libro por su id
    #[Route('/libros/{id}', name: 'libros_id', methods: ['GET'])]
    public function show(LibroRepository $repo, int $id): JsonResponse
    {
        $libro = $repo->find($id);
        if (!$libro) {
            return $this->json(['error' => 'Libro not found'], Response::HTTP_NOT_FOUND);
        }
        return $this->json($libro);
    }


    //Eliminar un libro por su id
    #[Route('/libros/{id}', name: 'libros_delete', methods: ['DELETE'])]
    public function delete(LibroRepository $repo, int $id, EntityManagerInterface $em): JsonResponse
    {
        $libro = $repo->find($id);
        if (!$libro) {
            return $this->json(['error' => 'Libro not found'], Response::HTTP_NOT_FOUND);
        }
        $em->remove($libro);
        $em->flush();


        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    // //Crear libro
    // #[Route('/libros', name: 'libros_create', methods: ['POST'])]
    // public function create(Request $request, EntityManagerInterface $em): JsonResponse
    // {
    //     $data = json_decode($request->getContent(), true);
    //     if ($data === null) {
    //         return $this->json(['error' => 'Invalid JSON'], 400);
    //     }
    //     if (isset($data["titulo"]) && !empty(trim($data["titulo"]))) {
    //         $libro = new Libro();
    //         $libro->setTitulo($data["titulo"]);

    //         $em->persist($libro);
    //         $em->flush();

    //         return $this->json($libro, 201);
    //     } else {
    //         return $this->json(['error' => 'El campo titulo es obligatorio'], 400);
    //     }
    // }

    #[Route('/libros', name: 'libro_create_validacion_symf', methods: ['POST'])]

    public function createLibroConValidacion(
        Request $request,
        ValidatorInterface $validator,
        EntityManagerInterface $em
    ): JsonResponse {

        $data = json_decode($request->getContent(), true);
        if ($data === null) {
            return $this->json(["error" => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }
        $error = $this->formatResponseOnInvalidFields($data, "titulo", 'Invalid JSON');
        if ($error) {
            return $this->json($error, Response::HTTP_BAD_REQUEST);
        }

        $error = $this->formatResponseOnInvalidFields($data, "descripcion", 'Invalid JSON');
        if ($error) {
            return $this->json($error, Response::HTTP_BAD_REQUEST);
        }




        $libro = new Libro();
        //Vamos a forzar que en el POST vengan todos los atributos (salvo el id)
        $libro->setTitulo($data['titulo'] ?? null);
        $libro->setDescripcion($data["descripcion"]);


        $errors = $validator->validate($libro);

        if (count($errors) > 0) {
            $formatArray = $this->formatInvalidErrorList($errors);
            return $this->json($formatArray, Response::HTTP_BAD_REQUEST);
        }


        $em->persist($libro);
        $em->flush();

        return $this->json($libro, 201);
    }


    #[Route('/libros/{id}', name: 'libro_update', methods: ['PUT'])]

    public function updateLibro(
        int $id,
        LibroRepository $repo,
        Request $request,
        ValidatorInterface $validator,
        EntityManagerInterface $em
    ): JsonResponse {

        $libro = $repo->find($id);

        if (!$libro) {
            return $this->json(['error' => 'Libro not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if ($data === null) {
            return $this->json(["error" => 'Invalid JSON'], 400);
        }

        $error = $this->formatResponseOnInvalidFields($data, "titulo", 'Invalid JSON');
        if ($error) {
            return $this->json($error, Response::HTTP_BAD_REQUEST);
        }

        $error = $this->formatResponseOnInvalidFields($data, "descripcion", 'Invalid JSON');
        if ($error) {
            return $this->json($error, Response::HTTP_BAD_REQUEST);
        }


        $libro->setTitulo($data['titulo'] ?? null);
        $libro->setDescripcion($data["descripcion"]);


        $errors = $validator->validate($libro);

        if (count($errors) > 0) {
            $formatArray = $this->formatInvalidErrorList($errors);
            return $this->json($formatArray, Response::HTTP_BAD_REQUEST);
        }







        $em->flush();

        return $this->json($libro, 200);
    }


    // #[Route('/libros/{id}', name: 'libro_update_partial', methods: ['PATCH'])]

    // public function updateParcialLibro(
    //     int $id,
    //     LibroRepository $repo,
    //     Request $request,
    //     EntityManagerInterface $em
    // ): JsonResponse {

    //     $libro = $repo->find($id);

    //     if (!$libro) {
    //         return $this->json(['error' => 'Libro not found'], Response::HTTP_NOT_FOUND);
    //     }

    //     $data = json_decode($request->getContent(), true);
    //     if ($data === null) {
    //         return $this->json(["error" => 'Invalid JSON'], 400);
    //     }



    //     if (isset($data["titulo"])) {
    //         try {
    //             $titulo = $this->validateFieldString("titulo",  self::MIN_LENGTH_TITULO, self::MAX_LENGTH_TITULO, $data);
    //             $libro->setTitulo($titulo);
    //         } catch (Exception $ex) {
    //             return $this->json(["error" => $ex->getMessage()], 400);
    //         }
    //     }

    //     if (isset($data["descripcion"])) {
    //         try {
    //             $descripcion = $this->validateFieldString(
    //                 "descripcion",
    //                 self::MIN_LENGTH_DESC,
    //                 self::MAX_LENGTH_DESC,
    //                 $data
    //             );
    //             $libro->setDescripcion($descripcion);
    //         } catch (Exception $ex) {
    //             return $this->json(["error" => $ex->getMessage()], 400);
    //         }
    //     }



    //     $em->flush();

    //     return $this->json($libro, 200);
    // }

    #[Route('/libros/{id}', name: 'libro_update_partial_validacion', methods: ['PATCH'])]

    public function updateParcialLibroConValidacion(
        int $id,
        LibroRepository $repo,
        Request $request,
        ValidatorInterface $validator,
        EntityManagerInterface $em
    ): JsonResponse {

        $libro = $repo->find($id);

        if (!$libro) {
            return $this->json(['error' => 'Libro not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if ($data === null) {
            return $this->json(["error" => 'Invalid JSON'], 400);
        }



        if (isset($data["titulo"])) {
            $libro->setTitulo($data["titulo"]);
        }

        if (isset($data["descripcion"])) {

            $libro->setDescripcion($data["descripcion"]);
        }

        $errors = $validator->validate($libro);

        if (count($errors) > 0) {
            $formatArray = $this->formatInvalidErrorList($errors);
            return $this->json($formatArray, Response::HTTP_BAD_REQUEST);
        }


        $em->flush();

        return $this->json($libro, 200);
    }

    // private function validateFieldString(string $fieldName, int $min, int $max, array $data): string
    // {
    //     if (isset($data[$fieldName])) {
    //         $value = trim($data[$fieldName]);
    //         if (!empty($value)) {

    //             if (strlen($value) >= $min  && strlen($value) <= $max) {
    //                 return $value;
    //             } else {
    //                 throw new Exception("El campo $fieldName debe tener al menos " . $min . " caracteres y " .
    //                     "no puede superar " . $max . " caracteres");
    //             }
    //         }
    //     }

    //     throw new Exception("El campo $fieldName es obligatorio");
    // }

    private function formatInvalidErrorList(ConstraintViolationListInterface $errors): array
    {
        if (count($errors) > 0) {
            $formattedErrors = [];
            foreach ($errors as $error) {
                $formattedErrors[$error->getPropertyPath()] = $error->getMessage();
            }
            $formatArray = [
                'error' => 'Validation failed',
                'fields' => $formattedErrors
            ];
            return $formatArray;
        }
        return [];
    }

    private function formatResponseOnInvalidFields(array $data, string $fieldName, string $message)
    {
        if (!isset($data[$fieldName])) {
            return ["error" => $message];
        }
    }

   
}
