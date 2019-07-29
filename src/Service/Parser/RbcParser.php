<?php

namespace App\Service\Parser;

use App\Service\FileDownloader;
use App\Entity\News;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\DomCrawler\Crawler;

class RbcParser implements ParserInterface
{
    private $targetDirectory;
    private $dbDirectory;
    private $fileDownloader;

    public function __construct(
        string $targetDirectory,
        string $dbDirectory,
        FileDownloader $fileDownloader
    ){
        $this->targetDirectory = $targetDirectory;
        $this->dbDirectory = $dbDirectory;
        $this->fileDownloader = $fileDownloader;

        $this->httpClient = HttpClient::create();
    }

    public function getTargetDirectory()
    {
        return $this->targetDirectory;
    }

    public function getDbDirectory()
    {
        return $this->dbDirectory;
    }

    /**
     * @inheritdoc
     */
    public function getPayload(string $source) : array
    {
        $response = $this->httpClient->request('GET', $source);

        $content = json_decode($response->getContent(), true);
        $content = $content['items'] ?? [];

        return array_reduce($content, function ($carry, $item) {
            $data = $this->getData($item);
            $carry[$data['externalId']] = $data;

            return $carry;
        }, []);
    }

    protected function getData(array $element): array
    {
        $crawler = new Crawler($element['html']);
        $crawler = $crawler->filterXPath('//a');

        if (!$crawler->count()) throw new \Exception('Expression "//a" returned 0 elements');

        $id = $this->getId($crawler);

        $data = [
            'title'       => $this->getTitle($crawler),
            'tag'         => $this->getTag($crawler),
            'publishDate' => $this->getDatetime($element),
            'href'        => $this->getHref($crawler),
            'externalId'  => $id,
            'image'       => null,
            'description' => null,
        ];

        try {
            $descriptionResponse = $this->httpClient->request(
                'GET',
                $data['href'],
                ['timeout' => 5]
            );
    
            $descriptionDom = new Crawler($descriptionResponse->getContent());
            $contentDom = $descriptionDom->filterXPath("//div[contains(@data-io-article-url, '${id}')]");
    
            if ($contentDom->count() > 0) {
                $data['image'] = $this->getImage($contentDom);
                $data['description'] = $this->getDescription($contentDom);
            }
        } catch (\Exception $e) {}

        return $data;
    }

    protected function getId(Crawler $dom) : string
    {
        return str_replace('id_newsfeed_', '', $dom->attr('id'));
    }

    protected function getTitle(Crawler $dom) : string
    {
        $titleDom = $dom->filterXPath('//span[contains(@class, \'news-feed__item__title\')]');

        if (!$titleDom->count()) throw new \Exception('Selector span[contains(@class, \'news-feed__item__title\')] returned 0 elements');

        return trim($titleDom->text());
    }

    protected function getTag(Crawler $dom) : ?string
    {
        $tagDom = $dom->filterXPath('//span[contains(@class, \'news-feed__item__date-text\')]');

        if ($tagDom->count() > 0) {
            $tag = trim($tagDom->text());
            $tag = current(explode(',', $tag));
        }

        return $tag ?? null;
    }

    protected function getDatetime(array $element) : \DateTime
    {
        if (!isset($element['publish_date_t'])) throw new \Exception('Field `publish_date_t` must be exists in $element');

        $dateTime = new \DateTime;

        return $dateTime->setTimestamp($element['publish_date_t']);
    }

    protected function getHref(Crawler $dom) : string
    {
        return $dom->attr('href');
    }

    protected function getImage(Crawler $dom) : ?string
    {
        $imageDom = $dom->filterXPath('//img[@itemprop="image"]');
        if (!$imageDom->count()) $imageDom = $dom->filterXPath('//div[contains(@class, \'article__main-image__wrap\')]/img');
        if ($imageDom->count() > 0) {
            $src = $imageDom->attr('src');
            $src = $this->fileDownloader->download($src, $this->getTargetDirectory());
            $src = str_replace($this->getTargetDirectory(), $this->getDbDirectory(), $src);
        }

        return $src ?? null;
    }

    protected function getDescription(Crawler $dom) : ?string
    {
        $textDom = $dom->filterXPath('//p');

        if ($textDom->count() > 0) {
            $description = $textDom
                ->each(function (Crawler $node, $i) {
                    return "<p>" . $node->html() . "</p>";
                });
            
            $description = join('', $description);
        }

        return $description ?? null;
    }
}