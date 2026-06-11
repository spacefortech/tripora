<?php

namespace App\Controller;

use App\Repository\PlaceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GuideController extends AbstractController
{
    private $places;

    public function __construct(PlaceRepository $places)
    {
        $this->places = $places;
    }

    public function index(): Response
    {
        return $this->render('guide/index.html.twig', array(
            'citiesJson' => $this->encodeJson($this->places->findCitiesData()),
        ));
    }

    public function coolPlaces(): Response
    {
        return $this->render('guide/cool_places.html.twig', array(
            'coolPlacesJson' => $this->encodeJson($this->places->findCoolPlacesData('duisburg')),
        ));
    }

    public function apiCities(): JsonResponse
    {
        return new JsonResponse($this->places->findCitiesData());
    }

    public function apiCity(string $slug): JsonResponse
    {
        $city = $this->places->findCityData($slug);

        if (!$city) {
            return new JsonResponse(
                array('error' => 'Stadt nicht gefunden'),
                Response::HTTP_NOT_FOUND
            );
        }

        return new JsonResponse($city);
    }

    public function einzelattraktion(Request $request): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = max(6, (int) $request->query->get('perPage', 24));
        $cityQuery = (string) $request->query->get('city', '');
        $searchQuery = trim((string) $request->query->get('q', ''));
        $selectedCity = $cityQuery !== '' ? $this->places->findCityData($cityQuery) : null;
        $items = $this->places->findAttractionItems(
            $selectedCity ? $selectedCity['slug'] : null,
            $searchQuery
        );

        $totalItems = count($items);
        $totalPages = (int) ceil($totalItems / max(1, $perPage));
        $totalPages = max(1, $totalPages);
        $page = min($page, $totalPages);

        $offset = ($page - 1) * $perPage;
        $pageItems = array_slice($items, $offset, $perPage);

        return $this->render('guide/einzelattraktion.html.twig', array(
            'items' => $pageItems,
            'page' => $page,
            'perPage' => $perPage,
            'totalItems' => $totalItems,
            'totalPages' => $totalPages,
            'searchQuery' => $searchQuery,
            'selectedCity' => $selectedCity ? array(
                'slug' => $selectedCity['slug'],
                'displayName' => $selectedCity['displayName'],
            ) : null,
        ));
    }

    public function highlightsPaket(): Response
    {
        return $this->render('guide/highlights_paket.html.twig', array(
            'citiesJson' => $this->encodeJson($this->places->findCitiesData()),
        ));
    }

    private function encodeJson(array $data): string
    {
        $json = json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        return $json === false ? '{}' : $json;
    }
}
