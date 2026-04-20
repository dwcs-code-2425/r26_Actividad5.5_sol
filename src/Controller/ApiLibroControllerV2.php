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

use OpenApi\Attributes as OA;

#[Route('/api/v2', name: 'app_api_v2')]
final class ApiLibroControllerV2 extends AbstractController
{

    const MIN_LENGTH_TITULO = 2;
    const MAX_LENGTH_TITULO = 10;

    const MIN_LENGTH_DESC = 10;
    const MAX_LENGTH_DESC = 255;

    #[Route('/libros', name: 'libros', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v2/libros',
        summary: 'Lista de libros',
        description: 'Devuelve un listado completo de libros',
        tags: ['Libros'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado de libros',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'integer'),
                            new OA\Property(property: 'titulo', type: 'string'),
                            new OA\Property(property: 'descripcion', type: 'string')
                        ]
                    )
                )
            )
        ]
    )]
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



    #[Route('/libros', name: 'libro_create_validacion_symf', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v2/libros',
        summary: 'Crea un nuevo libro',
        tags: ['Libros'],

        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['titulo'],
                properties: [
                    new OA\Property(property: 'titulo', type: 'string'),
                    new OA\Property(property: 'descripcion', type: 'string')
                ]
            )
        ),

        responses: [
            new OA\Response(
                response: 201,
                description: 'Libro creado',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'titulo', type: 'string'),
                        new OA\Property(property: 'descripcion', type: 'string')
                    ]
                )
            )
        ]
    )]
    public function createLibroConValidacion(
        Request $request,
        ValidatorInterface $validator,
        EntityManagerInterface $em
    ): JsonResponse {

        $data = json_decode($request->getContent(), true);
        if ($data === null) {
            return $this->json(["error" => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }
        // $error = $this->formatResponseOnInvalidFields($data, "titulo", 'Invalid JSON');
        // if ($error) {
        //     return $this->json($error, Response::HTTP_BAD_REQUEST);
        // }

        // $error = $this->formatResponseOnInvalidFields($data, "descripcion", 'Invalid JSON');
        // if ($error) {
        //     return $this->json($error, Response::HTTP_BAD_REQUEST);
        // }




        $libro = new Libro();
        //Vamos a forzar que en el POST vengan todos los atributos (salvo el id)

        $libro->setTitulo($this->trimOrNull($data['titulo'] ?? null));
        $libro->setDescripcion($this->trimOrNull($data['descripcion'] ?? null));



        $errors = $validator->validate($libro, null, ["create"]);

        if (count($errors) > 0) {
            $formatArray = $this->formatInvalidErrorList($errors);
            return $this->json($formatArray, Response::HTTP_BAD_REQUEST);
        }


        $em->persist($libro);
        $em->flush();

        return $this->json($libro, Response::HTTP_CREATED);
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
            return $this->json(["error" => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        // $error = $this->formatResponseOnInvalidFields($data, "titulo", 'Invalid JSON');
        // if ($error) {
        //     return $this->json($error, Response::HTTP_BAD_REQUEST);
        // }

        $error = $this->formatResponseOnInvalidFields($data, "descripcion", 'Invalid JSON');
        if ($error) {
            return $this->json($error, Response::HTTP_BAD_REQUEST);
        }


        //Aplicamos trim() a los campos string para evitar que se envíen valores con espacios en blanco al inicio o al final
        // Si el valor es null, se asigna null directamente al campo correspondiente. 
        // trim(null) ="" (cadena vacía), lo que podría causar problemas de validación si el campo permite valores nulos.
        $libro->setTitulo($this->trimOrNull($data['titulo'] ?? null));

        $libro->setDescripcion($this->trimOrNull($data['descripcion'] ?? null));

        $errors = $validator->validate($libro, null, ["replace"]);

        if (count($errors) > 0) {
            $formatArray = $this->formatInvalidErrorList($errors);
            return $this->json($formatArray, Response::HTTP_BAD_REQUEST);
        }







        $em->flush();

        return $this->json($libro, Response::HTTP_OK);
    }




    #[Route('/libros/{id}', name: 'libro_update_partial_validacion', methods: ['PATCH'])]
    #[OA\Patch(
        tags: ['Libros', 'Librosv2'],
        // Parámetro obligatorio en la ruta
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID del libro',
                schema: new OA\Schema(
                    type: 'integer',
                    format: 'int64',
                    example: 123
                )
            )
        ],


        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'titulo', type: 'string'),
                    new OA\Property(property: 'descripcion', type: 'string')
                ]
            )
        ),

        responses: [
            new OA\Response(
                response: 404,
                description: 'Libro no encontrado',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Libro not found')
                    ]
                )
            ),


            new OA\Response(
                response: 400,
                description: 'JSON inválido o error de validación',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Validation failed'),
                        new OA\Property(
                            property: 'fields',
                            type: 'object',
                            additionalProperties: true
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 200,
                description: 'Actualización parcial exitosa',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'titulo',   type: 'string'),
                        new OA\Property(property: 'descripcion',   type: 'string'),
                    ]
                )
            )



        ]
    )]
    /**
     * Actualizar parcialmente un Libro
     * @param int $id Identificador del libro a actualizar
     * @param LibroRepository $repo
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
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
            return $this->json(["error" => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }



        if (array_key_exists("titulo", $data)) {
            $libro->setTitulo($this->trimOrNull($data['titulo'] ?? null));
        }

        if (array_key_exists("descripcion", $data)) {
            $libro->setDescripcion($this->trimOrNull($data['descripcion'] ?? null));
        }

        $errors = $validator->validate($libro, null, ["update"]);

        if (count($errors) > 0) {
            $formatArray = $this->formatInvalidErrorList($errors);
            return $this->json($formatArray, Response::HTTP_BAD_REQUEST);
        }


        $em->flush();

        return $this->json($libro, Response::HTTP_OK);
    }


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
        //Para permitir que un campo exista y su valor sea null, en lugar de  !isset($data[$fieldName])  se puede usar array_key_exists para validar solo la existencia de la clave en el array, sin importar su valor (null, vacío, etc.)
        //if (!isset($data[$fieldName])) {
        if (!array_key_exists($fieldName, $data)) {
            return ["error" => $message];
        }
    }

    private function trimOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return trim($value);
    }
}
