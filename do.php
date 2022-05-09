<?php
require __DIR__.'/vendor/autoload.php';

use Lib\GtagFilter;

//主網址
$MasterURL = "";
//有參數，使用參數
if(isset($argv[1]) && $argv[1]) $MasterURL = $argv[1];
//參數2 專案名稱 建立目錄 放入sitemap
$ProjectTitle = "Website";//預設名稱
if(isset($argv[2]) && $argv[2]) $ProjectTitle = $argv[2];

//補充網址
$extraURL = [];

//之前進度紀錄載入
$PreDataFileTxt = "";
$file_path = __DIR__."/".$ProjectTitle."/sitemap.txt";
if( !is_dir($ProjectTitle)) mkdir($ProjectTitle,0777,true);
if( file_exists($file_path) && filesize($file_path) ){
    //刪除檔案最後一行，再重新写入文件，確保最後一個網址重新被執行。
    $file = $fp = fopen($file_path, 'r') or die("Unable to open file!");
    while(!feof($file)) {
        $fp = fgets($file);
        if($fp) {
            $content[] = $fp;
        }
    }
    array_pop($content);
    fclose($file);
    $file = fopen($file_path, 'w+');
    fwrite($file, implode("", $content));
    fclose($file);
    //載入
    $file = fopen($file_path, "r") or die("Unable to open file!");
    $PreDataFileTxt =  fread($file,filesize($file_path));
    fclose($file);
}
//排除字串
$ExcludeArray=[];
$ExcludeArray[] = "member";
$ExcludeArray[] = "shopcart";

//初始化
$oGtagFilter = new GtagFilter($MasterURL,$extraURL,$PreDataFileTxt,$file_path,$ExcludeArray);

//開始爬蟲
$oGtagFilter->exec();