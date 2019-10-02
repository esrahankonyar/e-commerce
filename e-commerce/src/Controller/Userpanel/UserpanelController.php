<?php

namespace App\Controller\Userpanel;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/userpanel")
 */
class UserpanelController extends AbstractController
{
    /**
     * @Route("/show", name="userpanel_show")
     */
    public function index()
    {
        return $this->render('userpanel/show.html.twig');
    }


    /**
     * @Route("/edit", name="userpanel_edit", methods="GET|POST")
     */
    public function edit(Request $request):Response
    {
        $usersession=$this->getUser(); // oturum açılmış kullanıcının bilgileri
        $user=$this->getDoctrine()
            ->getRepository(User::class)
            ->find($usersession->getid());

        if($request->isMethod('POST')) {
            $submittedToken = $request->request->get('token');
            if ($this->isCsrfTokenValid('user-form', $submittedToken)) {
                $user->setName($request->request->get("name"));
                $user->setPassword($request->request->get("password"));
                $user->setAddress($request->request->get("address"));
                $user->setCity($request->request->get("city"));
                $user->setPhone($request->request->get("phone"));
                $this->getDoctrine()->getManager()->flush();
                $this->addFlash('success', 'Kullanıcı bilgileriniz başarıyla güncellendi.');
                return $this->redirectToRoute('userpanel_show');

            }
        }
        return $this->render('userpanel/edit.html.twig',['user'=>$user]);
    }




}
