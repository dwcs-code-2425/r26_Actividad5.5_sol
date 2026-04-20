<?php

namespace App\Tests\Controller;

use App\Repository\LibroRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ApiLibroControllerV2Test extends WebTestCase
{
    private ?KernelBrowser $client;
    private LibroRepository $libroRepository;

    protected function setUp(): void
    {

        $this->client = static::createClient();

        // Desde el contenedor se pueden cargar otras clases: repositorios, servicios,etc.
         $this->libroRepository = $this->client->getContainer()->get(LibroRepository::class);
        // $entityManager = $this->client->getContainer()->get('doctrine')->getManager();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Opcional: liberar memoria de objetos pesados
        $this->client = null;
    }

    public function testCreateLibroOk(): void
    {
        $this->client->request(
            'POST',
            '/api/v2/libros',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'titulo' => 'Libro test',
                'descripcion' => 'Descripcion válida de prueba'
            ])
        );

        $this->assertResponseStatusCodeSame(201);

        $content = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $content);
        $this->assertEquals('Libro test', $content['titulo']);
        $this->assertEquals('Descripcion válida de prueba', $content['descripcion']);

        $libro = $this->libroRepository->find($content['id']);

        $this->assertNotNull($libro);
        $this->assertEquals('Libro test', $libro->getTitulo());
        $this->assertEquals('Descripcion válida de prueba',$libro->getDescripcion() );

    }


    public function testCreateLibroConDescripcionNullOK(): void
    {
        $this->client->request(
            'POST',
            '/api/v2/libros',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'titulo' => 'Libro test',
                'descripcion' => null
            ])
        );

        $this->assertResponseStatusCodeSame(201);

        $content = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $content);
        $this->assertEquals('Libro test', $content['titulo']);
        $this->assertNull($content['descripcion']);
    }

    public function testCreateLibroConSoloDescripcionNOK(): void
    {
        $this->client->request(
            'POST',
            '/api/v2/libros',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'descripcion' => '1234567890'
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }



    public function testCreateLibroSoloTitulo(): void
    {
        $this->client->request(
            'POST',
            '/api/v2/libros',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'titulo' => 'Libro test'

            ])
        );

        $this->assertResponseStatusCodeSame(201);

        $content = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $content);
        $this->assertEquals('Libro test', $content['titulo']);
    }

    public function testCreateLibroBadRequest(): void
    {


        $this->client->request(
            'POST',
            '/api/v2/libros',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function testGetLibros(): void
    {


        $this->client->request('GET', '/api/v2/libros');

        $this->assertResponseIsSuccessful();
    }

    public function testPatchLibroSoloTituloOK(): void
    {


        // primero crear uno
        $this->client->request(
            'POST',
            '/api/v2/libros',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'titulo' => 'Original',
                'descripcion' => 'Descripcion válida para test'
            ])
        );

        $this->assertResponseStatusCodeSame(201);

        $content = json_decode($this->client->getResponse()->getContent(), true);
        $id = $content['id'];

        // PATCH
        $this->client->request(
            'PATCH',
            "/api/v2/libros/$id",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'titulo' => 'Modificado'
            ])
        );

        $this->assertResponseIsSuccessful();
    }


    public function testPatchLibroSoloDescOK(): void
    {


        // primero crear uno
        $this->client->request(
            'POST',
            '/api/v2/libros',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'titulo' => 'Original',
                'descripcion' => 'Descripcion válida para test'
            ])
        );

        $this->assertResponseStatusCodeSame(201);

        $content = json_decode($this->client->getResponse()->getContent(), true);
        $id = $content['id'];

        // PATCH
        $this->client->request(
            'PATCH',
            "/api/v2/libros/$id",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'descripcion' => 'Modificado'
            ])
        );

        $this->assertResponseIsSuccessful();
        $content = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $content);
        $this->assertEquals($id, $content["id"]);

        $this->assertArrayHasKey('titulo', $content);
        $this->assertEquals('Original', $content['titulo']);

        $this->assertArrayHasKey('descripcion', $content);
        $this->assertEquals('Modificado', $content['descripcion']);
    }



    public function testPatchLibroSoloDescNullOK(): void
    {


        // primero crear uno
        $this->client->request(
            'POST',
            '/api/v2/libros',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'titulo' => 'Original',
                'descripcion' => 'Descripcion válida para test'
            ])
        );

        $this->assertResponseStatusCodeSame(201);

        $content = json_decode($this->client->getResponse()->getContent(), true);
        $id = $content['id'];

        // PATCH
        $this->client->request(
            'PATCH',
            "/api/v2/libros/$id",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'descripcion' => null
            ])
        );

        $this->assertResponseIsSuccessful();
        $content = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $content);
        $this->assertEquals($id, $content["id"]);

        $this->assertArrayHasKey('titulo', $content);
        $this->assertEquals('Original', $content['titulo']);

        $this->assertArrayHasKey('descripcion', $content);
        $this->assertNull($content['descripcion']);
    }



    public function testPutLibroOK(): void
    {


        // primero crear uno
        $this->client->request(
            'POST',
            '/api/v2/libros',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'titulo' => 'Original',
                'descripcion' => 'Descripcion válida para test'
            ])
        );

        $this->assertResponseStatusCodeSame(201);

        $content = json_decode($this->client->getResponse()->getContent(), true);
        $id = $content['id'];

        // PUT
        $this->client->request(
            'PUT',
            "/api/v2/libros/$id",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'titulo' => 'Modificado',
                'descripcion' => 'Modificada'
            ])
        );


        $contentPut = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertResponseIsSuccessful();
        $this->assertArrayHasKey('id', $contentPut);
        $this->assertEquals($id, $contentPut["id"]);

        $this->assertArrayHasKey('titulo', $contentPut);
        $this->assertEquals('Modificado', $contentPut['titulo']);

        $this->assertArrayHasKey('descripcion', $contentPut);
        $this->assertEquals("Modificada", $contentPut['descripcion']);
    }

      public function testPutLibroSoloTituloNOK(): void
    {


        // primero crear uno
        $this->client->request(
            'POST',
            '/api/v2/libros',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'titulo' => 'Original',
                'descripcion' => 'Descripcion válida para test'
            ])
        );

        $this->assertResponseStatusCodeSame(201);

        $content = json_decode($this->client->getResponse()->getContent(), true);
        $id = $content['id'];

        // PUT
        $this->client->request(
            'PUT',
            "/api/v2/libros/$id",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'titulo' => 'Modificado',
               
            ])
        );


      
        
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

      public function testPutLibroSoloDescNOK(): void
    {


        // primero crear uno
        $this->client->request(
            'POST',
            '/api/v2/libros',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'titulo' => 'Original',
                'descripcion' => 'Descripcion válida para test'
            ])
        );

        $this->assertResponseStatusCodeSame(201);

        $content = json_decode($this->client->getResponse()->getContent(), true);
        $id = $content['id'];

        // PUT
        $this->client->request(
            'PUT',
            "/api/v2/libros/$id",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'descripcion' => 'Modificado',
               
            ])
        );


      
        
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }


    public function testDeleteLibro(): void
    {


        $this->client->request(
            'POST',
            '/api/v2/libros',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'titulo' => 'A borrar',
                'descripcion' => 'Descripcion válida de prueba'
            ])
        );

        $content = json_decode($this->client->getResponse()->getContent(), true);
        $id = $content['id'];

        $this->client->request('DELETE', "/api/v2/libros/$id");

        $this->assertResponseStatusCodeSame(204);
    }
}
