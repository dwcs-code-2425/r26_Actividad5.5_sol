<?php

namespace App\Controller;

use App\Entity\Libro;
use App\Repository\LibroRepository;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;




#[Route('/api', name: 'app_api_')]
final class ApiLibroController extends AbstractController
{


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
            return $this->json(['error' => 'Libro not found'], 404);
        }
        return $this->json($libro);
    }


    //Eliminar un libro por su id
    #[Route('/libros/{id}', name: 'libros_delete', methods: ['DELETE'])]
    public function delete(LibroRepository $repo, int $id, EntityManagerInterface $em): JsonResponse
    {
        $libro = $repo->find($id);
        if (!$libro) {
            return $this->json(['error' => 'Libro not found'], 404);
        }
        $em->remove($libro);
        $em->flush();


        return $this->json(null, 204);
    }

    //Crear libro
    #[Route('/libros', name: 'libros_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if ($data === null) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }
        if (isset($data["titulo"]) && !empty(trim($data["titulo"]))) {
            $libro = new Libro();
            $libro->setTitulo($data["titulo"]);

            $em->persist($libro);
            $em->flush();

            return $this->json($libro, 201);
        } else {
            return $this->json(['error' => 'El campo titulo es obligatorio'], 400);
        }


    }
}