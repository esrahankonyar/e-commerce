<?php

namespace App\Controller\Admin;

use App\Entity\Admin\Product;
use App\Form\Admin\ProductType;
use App\Repository\Admin\CategoryRepository;
use App\Repository\Admin\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/product")
 */
class ProductController extends AbstractController
{
    /**
     * @Route("/", name="admin_product_index", methods="GET")
     */
    public function index(ProductRepository $productRepository): Response
    {
        $em=$this->getDoctrine()->getManager();
        $sql= "SELECT p.id, c.title
                FROM category c, product p
                WHERE p.category_id=c.id";
        $statement=$em->getConnection()->prepare($sql);
        $statement->execute();
        $categories=$statement->fetchAll();
        return $this->render('admin/product/index.html.twig', ['products' => $productRepository->findAll(),
            'categories'=>$categories]);
    }

    /**
     * @Route("/new", name="admin_product_new", methods="GET|POST")
     */
    public function new(Request $request, CategoryRepository $categoryrepository): Response
    {
        $catlist = $categoryrepository->findAll();
        $catname = $categoryrepository->findBy(['id'=>0]);
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($product);
            $em->flush();

            return $this->redirectToRoute('admin_product_index');
        }

        return $this->render('admin/product/new.html.twig', [
            'product' => $product,
            'catlist' => $catlist,
            'catname' => $catname,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="admin_product_show", methods="GET")
     */
    public function show(Product $product): Response
    {
        return $this->render('admin/product/show.html.twig', ['product' => $product]);
    }

    /**
     * @Route("/{id}/edit", name="admin_product_edit", methods="GET|POST")
     */
    public function edit(Request $request, $id, Product $product, CategoryRepository $categoryrepository): Response
    {
        $catlist = $categoryrepository->findAll();
        $catname = $categoryrepository->findBy(['id'=>$product->getCategoryId()]);

        //$catname = $categoryrepository->findBy(['id' => 0]);
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'Kayıt Güncelleme Başarılı');
            return $this->redirectToRoute('admin_product_edit', ['id' => $product->getId()]);
        }

        return $this->render('admin/product/edit.html.twig', [
            'product' => $product,
            'catlist'=>$catlist,
            'catname'=>$catname,
            'form' => $form->createView(),
        ]);
    }


    /**
     * @Route("/{id}/iedit", name="admin_product_iedit", methods="GET|POST")
     */
    public function iedit(Request $request, $id, Product $product): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {

            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('admin_product_index', ['id' => $product->getId()]);
        }

        return $this->render('admin/product/image_edit.html.twig', [
            'product' => $product,
            'id' => $id,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/iupdate", name="admin_product_iupdate", methods="POST")
     */
    public function iupdate(Request $request, $id, Product $product): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);
        //dump($request);
        //die();
        $file=$request->files->get('imagename');
        $fileName=$this->generateUniqueFileName().'.'.$file->guessExtension();
        // Move the file to the directory where brochures are stored
        try {
            $file->move(
                $this->getParameter('images_directory'),//services.yaml de tanımladığımız resim yolu.
                $fileName
            );
        } catch (FileException $e) {
            // ... handle exception if something happens during file upload
        }
        $product->setImage($fileName);
        $this->getDoctrine()->getManager()->flush();
        return $this->redirectToRoute('admin_product_iedit', ['id' => $product->getId()]);

    }


    /**
     * @Route("/{id}", name="admin_product_delete", methods="DELETE")
     */
    public function delete(Request $request, Product $product): Response
    {
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($product);
            $em->flush();
        }

        return $this->redirectToRoute('admin_product_index');
    }


    /**
     * @return string
     */
    private function generateUniqueFileName()
    {
        // md5() reduces the similarity of the file names generated by
        // uniqid(), which is based on timestamps
        return md5(uniqid());
    }
}
