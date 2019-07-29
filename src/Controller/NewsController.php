<?php
namespace App\Controller;

use App\Entity\News;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class NewsController extends AbstractController
{
    /**
     * @Route("/", name="news_list")
     */
    public function list(Breadcrumbs $breadcrumbs)
    {
        $breadcrumbs->addItem("Лента новостей", $this->get("router")->generate("news_list"));
        $list = $this->getDoctrine()
            ->getRepository(News::class)
            ->findBy([], ['publishDate' => 'desc'], 15);

        return $this->render('news/list.html.twig', [
            'news' => $list,
        ]);
    }

    /**
     * @Route("/{id}", name="news_show")
     */
    public function show($id, Breadcrumbs $breadcrumbs)
    {
        $breadcrumbs->addItem("Лента новостей", $this->get("router")->generate("news_list"));

        $item = $this->getDoctrine()
            ->getRepository(News::class)
            ->findOneBy(['id' => $id], ['publishDate' => 'desc'], 15);

        $breadcrumbs->addItem($item->getTitle(), '/' . $id);

        return $this->render('news/card.html.twig', [
            'item' => $item,
        ]);
    }
}