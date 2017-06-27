<?php
namespace liw\service;
use liw\models\Image;
use Symfony\Component\DomCrawler\Crawler;

class Parser
{
    private $arrayImage = [];
    private $arrayLinks = [];

    public function __construct()
    {
    }

    private function normalizeUrl($baseUrl, $url){
          $string  = $baseUrl . ltrim($url, '/');

          array_push($this->arrayLinks, $string);
        return $string;
    }
    private function getArrayUrl($baseUrl, $url){
        $string  = $baseUrl . ltrim($url, '/');

        $file_headers = @get_headers($string);

        if (in_array($string, $this->arrayLinks) || !$file_headers || $file_headers[0] == 'HTTP/1.1 404 Not Found'){
            return false;
        } else {
            return true;
        }
    }

    private function getHtml($url){
            $file = __DIR__ . '/cache/' . md5($url);
            if (file_exists($file)) {
                return file_get_contents($file);
            } else {
                $html = file_get_contents($url);
                file_put_contents($file, $html);
                return $html;
            }
    }

    private function crawler($baseUrl, $url){
        return new Crawler($this->getHtml($this->normalizeUrl($baseUrl, $url)));
    }

    private function getPageImage($baseUrl, $pageUrl){
    $url = $this->parse_url_if_valid($baseUrl);

    $arr = $this->crawler($url, $pageUrl)
        ->filter('img')
        ->each(function (Crawler $image){
            return [
                'image' => $image->attr('src'),
            ];
        });
    $array = [];
    foreach ($arr as $item){
        $imageUrl = $this->checkImage($item['image'], $baseUrl);
        echo $imageUrl . "\n";
        array_push($array, $imageUrl);
    }
    $image = new Image();
    $image->setImageUrl($array);
    $this->arrayImage[$url . ltrim($pageUrl, '/')] = $image;

}

    private function getPageUrl($baseUrl, $pageUrl){
        $url = $this->parse_url_if_valid($baseUrl);

        $arr = $this->crawler($url, $pageUrl)
            ->filter('a')
            ->each(function (Crawler $link) {
                return [
                    'url' => $link->attr('href'),
                ];
            });
        $array = [];

        foreach ($arr as $item) {
            $url = $this->checkUrl($item['url']);
            if ($url != ""){
                array_push($array, $url);
            }
        }
        return $array;
    }

    private function parse_url_if_valid($url)
    {
        $arUrl = parse_url($url);
        $ret = null;

        if (!array_key_exists("scheme", $arUrl) || !in_array($arUrl["scheme"], array("http", "https")))
            $arUrl["scheme"] = "http";
        if (array_key_exists("host", $arUrl) && !empty($arUrl["host"])) {
            $ret = sprintf("%s://%s%s", $arUrl["scheme"],
                $arUrl["host"], $arUrl["path"]);
        }  else if (preg_match("/^\w+\.[\w\.]+(\/.*)?$/", $arUrl["path"])) {
            $ret = sprintf("%s://%s", $arUrl["scheme"], $arUrl["path"]);
        }

        if ($ret && empty($ret["query"]))
            $ret .= sprintf("%s", $arUrl["query"]);

        return $ret;
    }
    private function checkUrl($url){
        if ($url != "" && $url != null && !empty($url) && $url != "#" && !preg_match('/^(http|https):\/\//i', $url) && substr($url, 0, 6)!='mailto' && !preg_match("/^(\S+)(http|https):\/\//i", $url) ){
            if (substr($url, 0, 2) == "./"){
                return trim(substr_replace($url, '', 0, 2));
            } elseif (substr($url, 0, 1) != "/"){
                return trim($url);
            }
            return trim(substr_replace($url, '', 0, 1));
        }
        return false;
    }

    private function checkImage($url, $baseUrl){
        if (preg_match("/^(\.\.\/\.\.\/)(\S+)$/i", $url)){
            $url = preg_replace("/^(\.\.\/\.\.\/)(\S+)$/i", '$2', $url);
            return $baseUrl . $url;
        }  elseif (preg_match("/^(\.\.\/)(\S+)$/i", $url)){
            $url = preg_replace("/^(\.\.\/)(\S+)$/i", '$2', $url);
            return $baseUrl . $url;
        } elseif (preg_match("/^(\S+)(\/\.\.\/\.\.\/)(\S+)$/i", $url)){
            $url = preg_replace("/^(\S+)(\/\.\.\/\.\.\/)(\S+)$/i", '$3', $url);
            return $baseUrl . $url;
        } elseif (preg_match("/^(\/\S+)$/i", $url)){
            $url = preg_replace("/^(\/)(\S+)$/i", '$2', $url);
            return $baseUrl . $url;
        } elseif(preg_match("/^(\.\.\/\.\.\/\.\.\/)(\S+)$/i", $url)){
            $url = preg_replace("/^(\.\.\/\.\.\/)(\S+)$/i", '$2', $url);
            return $baseUrl . $url;
        } elseif (preg_match("/^(\S+)$/i", $url))
            return $baseUrl . $url;
    }

    public function parse($baseUrl, $url = "/"){

        $this->getPageImage($baseUrl, $url);

        $arr = $this->getPageUrl($baseUrl, $url);
        foreach ($arr as $item){
            if ($this->getArrayUrl($baseUrl, $item) == false){
                continue;
            }
            //echo $item . "\n";
            $this->parse($baseUrl, $item);
        }

    }


    public function getArrayImage()
    {
        return $this->arrayImage;
    }

}