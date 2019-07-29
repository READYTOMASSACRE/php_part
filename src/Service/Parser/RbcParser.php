<?php

namespace App\Service\Parser;

use App\Service\FileDownloader;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\DomCrawler\Crawler;

class RbcParser implements ParserInterface
{
    /**
     * @var string
     */
    private $targetDirectory;

    /**
     * @var string
     */
    private $dbDirectory;

    /**
     * @var string
     */
    private $defaultSource;

    /**
     * @var \App\Service\FileDownloader
     */
    private $fileDownloader;

    /**
     * @param string $targetDirectory destination on server
     * @param string $dbDirectory destination for db
     * @param \App\Service\FileDownloader $fileDownloader
     */
    public function __construct(
        string $targetDirectory,
        string $dbDirectory,
        string $defaultSource,
        FileDownloader $fileDownloader
    ){
        $this->targetDirectory = $targetDirectory;
        $this->dbDirectory = $dbDirectory;
        $this->fileDownloader = $fileDownloader;
        $this->defaultSource = $defaultSource;

        $this->httpClient = HttpClient::create();
    }

    /**
     * Returns destination file on server
     * 
     * @return string
     */
    public function getTargetDirectory() : string
    {
        return $this->targetDirectory;
    }

    /**
     * Returns destination file for the db
     * 
     * @return string
     */
    public function getDbDirectory() : string
    {
        return $this->dbDirectory;
    }

    /**
     * Returns default source for parser
     * 
     * @return string
     */
    public function getDefaultSource() : string
    {
        return str_replace('{{date}}', time(), $this->defaultSource);
    }

    /**
     * @inheritdoc
     */
    public function getPayload(string $source = null) : array
    {
        $response = $this->httpClient->request('GET', $source ?? $this->getDefaultSource());

        $content = json_decode($response->getContent(), true);
        $content = $content['items'] ?? [];

        return array_reduce($content, function ($carry, $item) {
            $data = $this->getData($item);
            $carry[$data['externalId']] = $data;

            return $carry;
        }, []);
    }

    /**
     * Get data from one element and
     * trying fetch for description, image
     * 
     * @param array $element Element contains html and publish date
     * 
     * @return array Data of element
     */
    protected function getData(array $element): array
    {
        $crawler = new Crawler($element['html']);
        $crawler = $crawler->filterXPath('//a');

        if (!$crawler->count()) throw new \Exception('Expression "//a" returned 0 elements');

        // get id from html
        $id = $this->getId($crawler);

        // collect data from html
        $data = [
            'title'       => $this->getTitle($crawler),
            'tag'         => $this->getTag($crawler),
            'publishDate' => $this->getDatetime($element),
            'href'        => $this->getHref($crawler),
            'externalId'  => $id,
            'image'       => null,
            'description' => null,
        ];

        // try fetch detail news page
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

    /**
     * Get Id from dom element
     * 
     * @param \Symfony\Component\DomCrawler\Crawler $dom
     * 
     * @return string
     */
    protected function getId(Crawler $dom) : string
    {
        return str_replace('id_newsfeed_', '', $dom->attr('id'));
    }

    /**
     * Get Title from dom element
     * 
     * @param \Symfony\Component\DomCrawler\Crawler $dom
     * 
     * @return string
     */
    protected function getTitle(Crawler $dom) : string
    {
        $titleDom = $dom->filterXPath('//span[contains(@class, \'news-feed__item__title\')]');

        if (!$titleDom->count()) throw new \Exception('Selector span[contains(@class, \'news-feed__item__title\')] returned 0 elements');

        return trim($titleDom->text());
    }

    /**
     * Get Tag from dom element
     * 
     * @param \Symfony\Component\DomCrawler\Crawler $dom
     * 
     * @return string
     */
    protected function getTag(Crawler $dom) : ?string
    {
        $tagDom = $dom->filterXPath('//span[contains(@class, \'news-feed__item__date-text\')]');

        if ($tagDom->count() > 0) {
            $tag = trim($tagDom->text());
            $tag = current(explode(',', $tag));
        }

        return $tag ?? null;
    }

    /**
     * Get Datetime from element
     * 
     * @param array $element
     * 
     * @return \DateTime
     */
    protected function getDatetime(array $element) : \DateTime
    {
        if (!isset($element['publish_date_t'])) throw new \Exception('Field `publish_date_t` must be exists in $element');

        $dateTime = new \DateTime;

        return $dateTime->setTimestamp($element['publish_date_t']);
    }

    /**
     * Get Href from dom element
     * 
     * @param \Symfony\Component\DomCrawler\Crawler $dom
     * 
     * @return string
     */
    protected function getHref(Crawler $dom) : string
    {
        return $dom->attr('href');
    }

    /**
     * Get image from dom element
     * 
     * @param \Symfony\Component\DomCrawler\Crawler $dom
     * 
     * @return string|null
     */
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
    /**
     * Get description from dom element
     * 
     * @param \Symfony\Component\DomCrawler\Crawler $dom
     * 
     * @return string|null
     */
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