<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

use App\Entity\Order;
use App\Repository\ProductRepository;

class CheckoutController extends AbstractController
{
    public function __construct(private ManagerRegistry $doctrine)
    {
    }

    #[Route('/checkout', name: 'app_checkout')]
    public function checkout(Request         $request, ProductRepository $repo, SessionInterface $session,
                             MailerInterface $mailer): Response
    {
        $basket = $session->get('basket', []);
        $total = array_sum(array_map(function ($product) {
            return $product->getPrice();
        }, $basket));

        $order = new Order();

        $form = $this->createFormBuilder($order)
            ->add('name', TextType::class)
            ->add('email', TextType::class)
            ->add('address', TextareaType::class)
            ->add('save', SubmitType::class, ['label' => 'Confirm Order'])
            ->getForm();

        $form->handleRequest($request); // saves form values when page refresh

        if ($form->isSubmitted() && $form->isValid()) {
            $order = $form->getData();

            foreach ($basket as $product) {
                $order->getProducts()->add($repo->find($product->getId()));
            }

            $entityManager = $this->doctrine->getManager();
            $entityManager->persist($order);
            $entityManager->flush();

            $this->sendEmailConfirmation($order, $mailer);

            $session->set('basket', []); // empties basket after checkout

            return $this->render('checkout/confirmation.html.twig');
        }

        return $this->render('checkout/checkout.html.twig', [
            'total' => $total,
            'form' => $form->createView()
        ]);
    }

    //sends email after checkout
    private function sendEmailConfirmation(Order $order, MailerInterface $mailer)
    {
        $email = (new TemplatedEmail())
            ->from('symfony@eriksosis.com')
            ->to(new Address($order->getEmail(), $order->getName()))
            ->subject('Order confirmation')
            ->htmlTemplate('emails/order.html.twig')
            ->context(['order' => $order]);

        $mailer->send($email);

    }
}
