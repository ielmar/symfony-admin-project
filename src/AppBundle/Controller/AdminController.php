<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use AppBundle\Entity\Users;
use AppBundle\Form\UserType;
use AppBundle\Entity\Groups;
use AppBundle\Form\GroupType;

class AdminController extends Controller
{
    /**
    * @Route("/user", name="user_list")
    */
    public function listAction(Request $request)
    {
        $encoders = array(new XmlEncoder(), new JsonEncoder());
        $normalizers = array(new ObjectNormalizer());
        
        $serializer = new Serializer($normalizers, $encoders);

        // get api argument value
        $api = $request->query->get('api');

        // find all users
        $users = $this->getDoctrine()
        ->getRepository(Users::class)
        ->findAll();

        // if $api is set and is true, show as json
        if(!is_null($api) && $api == 'true')
        {
            $response = new Response();
            $response->setContent($serializer->serialize($users, 'json'));
            $response->headers->set('Content-Type', 'application/json');
            $response->setStatusCode(Response::HTTP_OK);

            return $response;
        }
        else
        {
            return $this->render('admin/index.html.twig', array(
                'users' => $users
            ));
        }
    }

    /**
    * @Route("/user/add", name="user_add")
    */
    public function addAction(Request $request)
    {
        $user = new Users();

        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $formData = $form->getData();

            $em = $this->getDoctrine()->getManager();
            $em->persist($formData);
            $em->flush();
    
            return $this->redirectToRoute('user_list');
        }

        return $this->render('admin/form.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
    * @Route("/user/{id}", name="show_user", requirements={"id": "\d+"})
    */
    public function showAction($id)
    {
        $user = $this->getDoctrine()
        ->getRepository(Users::class)
        ->find($id);

        return $this->render('admin/show.html.twig', array(
            'user' => $user
        ));
    }

    /**
    * @Route("/user/edit/{id}", name="edit_user", requirements={"id": "\d+"})
    */
    public function editAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository(Users::class)->find($id);
        $form = $this->createForm(UserType::class, $user);

        if (!$user) {
            throw $this->createNotFoundException(
                'No user found for id '.$id
            );
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $formData = $form->getData();
            $em->persist($formData);
            $em->flush();

            return $this->redirectToRoute('user_list');
        }

        return $this->render('admin/form.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
    * @Route("/user/delete/{id}", name="delete_user", requirements={"id": "\d+"})
    */
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository(Users::class)->find($id);
        $em->remove($user);
        $em->flush();
    
        return $this->redirectToRoute('user_list');
    }

    /**
    * @Route("/user/remove/{id}", name="remove_user_from_group", requirements={"id": "\d+"})
    */
    public function removeFromGroupAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository(Users::class)->find($id);

        if (!$user) {
            throw $this->createNotFoundException(
                'No user found for id '.$id
            );
        }

        $user->setGroup(null);

        $em->persist($user);
        $em->flush();

        return $this->redirectToRoute('user_list');

    }
}
