<?php

namespace App\Controller;

use App\Entity\OrderDetail;
use App\Entity\Orders;
use App\Form\OrdersType;
use App\Repository\OrderDetailRepository;
use App\Repository\OrdersRepository;
use App\Repository\ShopcartRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/orders")
 */
class OrdersController extends AbstractController
{
    /**
     * @Route("/", name="orders_index", methods="GET")
     */
    public function index(OrdersRepository $ordersRepository): Response
    {
        $user= $this->getUser();
        $userid= $user->getid();
        return $this->render('orders/index.html.twig', ['orders' => $ordersRepository->findBy(['userid' =>$userid ])]);
    }

    /**
     * @Route("/new", name="orders_new", methods="GET|POST")
     */
    public function new(Request $request, ShopcartRepository $shopcartRepository): Response
    {
        $orders = new Orders();
        $form = $this->createForm(OrdersType::class, $orders);
        $form->handleRequest($request);

        $user= $this->getUser();
        $userid=$user->getid();
        $total=$shopcartRepository->getUserShopCartTotal($userid);


        $submittedToken= $request->request->get('token');
        if($this->isCsrfTokenValid('form-order', $submittedToken)){
            if ($form->isSubmitted()) {
                $em = $this->getDoctrine()->getManager();

                $orders->setUserid($userid);
                $orders->setAmount($total);
                $orders->setStatus("New");

                $em->persist($orders);
                $em->flush();

                $orderid= $orders->getId();//yeni oluşturulan order satırının id sini verir
                $shopcart= $shopcartRepository->getUserShopCart($userid);

                foreach ($shopcart as $item){
                    //döngü içerisinde order tablosundaki veriler orderdetaile aktarılır.
                    $orderdetail=new OrderDetail();
                    $orderdetail->setOrderid($orderid);
                    $orderdetail->setUserid($user->getid()); // login user id
                    $orderdetail->setProductid($item["productid"]);
                    $orderdetail->setPrice($item["sprice"]);
                    $orderdetail->setQuantity($item["quantity"]);
                    $orderdetail->setAmount($item["total"]);
                    $orderdetail->setName($item["title"]);
                    $orderdetail->setStatus("Ordered");

                    $em->persist($orderdetail);
                    $em->flush();
                }
                $em = $this->getDoctrine()->getManager();
                $query=$em->CreateQuery('
                    DELETE FROM App\Entity\shopcart s WHERE s.userid=:userid ')
                    ->setParameter('userid', $userid);
                $query->execute();
                $this->addFlash('success', 'siparişiniz başarıyla gerçekleştirilmiştir Teşekkür Ederiz.');



                return $this->redirectToRoute('orders_index');
            }

        }
        $em = $this->getDoctrine()->getManager();
        $sql= "SELECT p.title, p.sprice, p.image, s.*
                FROM shopcart s, product p
                WHERE s.productid=p.id and userid= :userid ";

        $statement=$em->getConnection()->prepare($sql);
        $statement->bindValue('userid', $user->getid());
        $statement->execute();
        $shopcarts=$statement->fetchAll();

        return $this->render('orders/new.html.twig', [
            'order' => $orders,
            'total' => $total,
            'form' => $form->createView(),
            'shopcarts' => $shopcarts
        ]);
    }

    /**
     * @Route("/{id}", name="orders_show", methods="GET")
     */
    public function show(Orders $order, OrdersRepository $ordersRepository, OrderDetailRepository $orderDetailRepository): Response
    {
        $user= $this->getUser();
        $userid=$user->getid();
        $orderid=$order->getid();
        $orderdetail=$orderDetailRepository->findBy(
            ['orderid' =>$orderid]
        );
        return $this->render('orders/show.html.twig', [
            'order' => $order,
            'orderdetail' => $orderdetail,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="orders_edit", methods="GET|POST")
     */
    public function edit(Request $request, Orders $order): Response
    {
        $form = $this->createForm(OrdersType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('orders_index', ['id' => $order->getId()]);
        }

        return $this->render('orders/edit.html.twig', [
            'order' => $order,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="orders_delete", methods="DELETE")
     */
    public function delete(Request $request, Orders $order): Response
    {
        if ($this->isCsrfTokenValid('delete'.$order->getId(), $request->request->get('_token'))) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($order);
            $em->flush();
        }

        return $this->redirectToRoute('orders_index');
    }
}
