<?php
namespace Lib;

use voku\helper\HtmlDomParser;

class GtagFilter {
    //所有結果集合
    public $CollectionArray = [];
    //站點
    public $URL_Site = "";
    //所有要爬的網址
    public $URLTail = [];
    //深度紀錄
    public $deepLayer = 0;
    //檔案儲存路徑
    public $FilePath = "";
    //字串包含，則不儲存
    public $ExcludeStringArray = [];

    //
    public function __construct($site,$extraURL = [],$loadPreData="",$FilePath="",$ExcludeArray=[]){
        //設定主網域
        $this->URL_Site = $site;
        //預設帶入根目錄\
        $this->URLTail[] = "/";
        //載入之前進度
        foreach (explode("\n",$loadPreData) as $value){
            $this->URLTail[] = $value;
        }
        //放入額外補充網址
        foreach ($extraURL as $value){
            $this->addURLSingle($value);
        }
        //檔案路徑
        $this->FilePath = $FilePath;
        //字串包含則排除
        $this->ExcludeStringArray = $ExcludeArray;
    }
    //
    public function exec(){
        //預設網址
        foreach ($this->URLTail as $value) {
            $URL = $this->URL_Site.$value;
            $HTML_Code = $this->getContent($URL);
            $Temp = $this->getUrl($HTML_Code);
            $this->addURL(implode("\n",$Temp));
        }
    }
    //深度優先爬找網址
    public function addURL($String){
        //
        $this->deepLayer++;
        //移除空白
        $String = str_replace(" ","",$String);
        //根據換行切成陣列
        $Array = explode("\n",$String);
        //
        foreach ($Array as $value){
            $addUrl = $this->addURLSingle($value);
            //放入失敗代表此深度結束
            if(!$addUrl) continue;
            //繼續往下挖
            $FullURL = $this->URL_Site.$addUrl;
            $HTML_Code = $this->getContent($FullURL);
            if(!$HTML_Code) continue;
            //順帶抓取Google Tag
//            $MatchALLData = $this->filterGtag($HTML_Code);
//            $this->filterResultEncode($addUrl,$MatchALLData);
            //
            $URLList = $this->getUrl($HTML_Code);
            if(!$URLList) continue;
            $this->addURL(implode("\n",$URLList));
        }
        $this->deepLayer--;
    }
    public function addURLSingle($value){
        //如果有站點網址，先移除，只抓後半段
        $value = str_replace($this->URL_Site,"",$value);
        $value = str_replace("\n","",$value);
        $value = str_replace("\r","",$value);
        $value = str_replace("//","/",$value);
        //如果最後一個字元是/則去除
//        if(str_ends_with($value, "/")) $value = substr($value, 0, -1);
        //包含字串 則不收錄網址
        if (strpos($value, "tel:") !== false) return false;
        if (strpos($value, "javascript:;") !== false) return false;
        if (strpos($value, "gmap:;") !== false) return false;
        if (strpos($value, "#") !== false) return false;
        //非本網域網址，不收錄 如果網址正確，應該前面被取代掉 沒有http or https
        if (strpos($value, "http") !== false) return false;
        //空直不帶入
        if(!$value) return false;
        //不重複放入
        if( in_array($value,$this->URLTail) ) return false;
        //自訂不儲存的字串
        foreach ($this->ExcludeStringArray as $excludeString){
            if (strpos($value, $excludeString) !== false) return false;
        }
        //放入
        $this->URLTail[] = $value;
        echo "Depth:".$this->deepLayer."_Index:".count($this->URLTail)."_".$value.PHP_EOL;
        //寫入檔案
        $fWrite = fopen($this->FilePath,"a");
        fwrite($fWrite, $value.PHP_EOL);
        fclose($fWrite);
        //
        return $value;
    }
    //GET抓HTML Code
    public function getContent($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);//301跟著跳
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }
    public function filterGtag($string):array{
        $string = str_replace(array("\r", "\n", "\r\n", "\n\r"), '', $string);
        // gtag("參數1","參數2","參數3"); "or' ['|\"]
        // 參數1 ([A-Z|a-z|\d]+)
        // 參數2 ([A-Z|a-z|\d|\-|_]+)
        // 參數3 (\{\s*[^;]+\s*\}) {[不能有;]}
        $pattern = "/gtag\s*\(\s*['|\"]([A-Z|a-z|\d]+)['|\"]\s*,\s*['|\"]([A-Z|a-z|\d|\-|_]+)['|\"]\s*,\s*(\{\s*[^;]+\s*\})\s*\)\s*/";
        preg_match_all($pattern, $string, $matches_all);
        return $matches_all;
    }
    public function filterResultEncode($URL,$matches_all):array{
        $List = [];
        //按照正規()順序
        foreach ($matches_all[1] as $key => $matches){
            $List[$key]["Attribute"] = $matches;
        }
        foreach ($matches_all[2] as $key => $matches){
            $List[$key]["Title"] = $matches;
        }
        foreach ($matches_all[3] as $key => $matches){
            $List[$key]["Content"] = $matches;
        }
        //放入集合
        foreach ($List as $value){
            $this->CollectionArray[$URL][] = $value;
        }

        return $List;
    }
    //抓出該網址HTML內的所有連結
    public function getUrl($HTML_Code): array {
        $dom = HtmlDomParser::str_get_html($HTML_Code);
        $Link = [];
        foreach ( $dom->find('a') as $value ) {
            $data = $value->getAttribute("href");
            if($data)
                $Link[] =  $data;
        }
        return $Link;
    }
}