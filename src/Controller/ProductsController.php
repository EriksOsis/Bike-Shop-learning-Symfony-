<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ProductRepository;

class ProductsController extends AbstractController
{
    #[Route('/', name: 'app_products')]
    public function index(ProductRepository $repo): Response
    {
        $bikes = $repo->findBy([]);
        return $this->render('first/homepage.html.twig', [
            'bikes' => $bikes
        ]);
    }

    /**
     * @Route("/products/{id}")
     */
    public function details($id, Request $request, SessionInterface $session, ProductRepository $repo): Response
    {
        $bike = $repo->find($id);

        if ($bike === null) {
            throw $this->createNotFoundException('The product does not exist.');
        }

        // add to basket handling
        $basket = $session->get('basket', []);
        if ($request->isMethod('POST')) {
            $basket[$bike->getId()] = $bike;
            $session->set('basket', $basket);
        }

        $isInBasket = array_key_exists($bike->getId(), $basket);

        return $this->render('first/details.html.twig', [
            'bike' => $bike,
            'inBasket' => $isInBasket
        ]);
    }
}
