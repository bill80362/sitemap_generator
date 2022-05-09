<?php
ini_set('display_errors','on');     # 開啟錯誤輸出

session_start();
require 'vendor/autoload.php';
use Lib\GtagFilter;

//驗證權限
if( !(isset($_SESSION["login"]) && $_SESSION["login"]) ){
    $newURL = "/login.php";
    header('Location: '.$newURL);
    exit();
}

//init

$GTagResult = [];
//
if( isset($_POST["MasterURL"]) && $_POST["MasterURL"] ){
    $MasterURL = $_POST["MasterURL"];
    $oGtagFilter = new GtagFilter($MasterURL);
    //抓取HTML
    $URL_String = $_POST["URL_String"];
    foreach (explode("\n",$URL_String) as $value){
        //如果有站點網址，先移除，只抓後半段
        $value = str_replace($oGtagFilter->URL_Site,"",$value);
        $value = str_replace("\n","",$value);
        $value = str_replace("\r","",$value);
        $value = str_replace("//","/",$value);
        //空直不帶入
        if(!$value) continue;
        //抓ＨＴＭＬ
        $FullURL = $oGtagFilter->URL_Site.$value;
        $HTML_Code = $oGtagFilter->getContent($FullURL);
        //抓取Google Tag
        $MatchALLData = $oGtagFilter->filterGtag($HTML_Code);
        $oGtagFilter->filterResultEncode($FullURL,$MatchALLData);
        $GTagResult = $oGtagFilter->CollectionArray;
//var_dump($GTagResult);
    }
}





?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>GA4快篩工具</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.3/css/all.css" integrity="sha384-UHRtZLI+pbxtHCWp1t77Bi1L4ZtiqrqD80Kn4Z8NTSRyMA2Fd33n5dQ8lWUE00s/" crossorigin="anonymous">
    <link rel="stylesheet" href="https://unpkg.com/bootstrap-table@1.17.1/dist/bootstrap-table.min.css">
</head>
<body>


<main role="main" class="container">
    <div class="card">
        <div class="card-header">
            GA4快篩工具
        </div>
        <div class="card-body">
            <form method="post">
                <div>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label>主網域</label>
                                <input type="text" class="form-control" name="MasterURL" placeholder="https://www.iroo.com" value="https://www.iroo.com">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label>網址列表</label>
                                <textarea class="form-control" name="URL_String" rows="3"><?=$_POST["URL_String"]??"/"?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <button type="submit" class="btn btn-block btn-primary">開始</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <hr>
    <div id="accordion">
        <h4>GA4快篩結果</h4>
        <?php $i=0; ?>
        <?php foreach ($GTagResult as $URL => $Array) { ?>
        <?php $i++; ?>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#URL_DATA<?=$i?>" aria-expanded="false">
                        網址: <?=$URL?>
                    </button>
                </h5>
            </div>
            <div id="URL_DATA<?=$i?>" class="collapse" data-parent="#accordion">
                <div class="card-body">
                    <table
                        data-toggle="table"
                        data-search="true"
                        data-show-multi-sort="true"
                        data-show-columns="true"
                        data-show-export="true"
                    >
                        <thead>
                        <tr>
                            <th data-sortable="true">參數1</th>
                            <th data-sortable="true">參數2</th>
                            <th data-sortable="true">參數3</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($Array as $value) { ?>
                        <tr>
                            <td><?=$value["Attribute"]?></td>
                            <td><?=$value["Title"]?></td>
                            <td><?=$value["Content"]?></td>
                        </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php } ?>

    </div>


</main>


<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js" integrity="sha512-bLT0Qm9VnAYZDflyKcBaQ2gg0hSYNQrJ8RilYldYQ1FxQYoCLtUjuuRuZo+fjqhx/qtq/1itJ0C2ejDxltZVFg==" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/tableexport.jquery.plugin@1.10.21/tableExport.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tableexport.jquery.plugin@1.10.21/libs/jsPDF/jspdf.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tableexport.jquery.plugin@1.10.21/libs/jsPDF-AutoTable/jspdf.plugin.autotable.js"></script>
<script src="https://unpkg.com/bootstrap-table@1.17.1/dist/bootstrap-table.min.js"></script>
<script src="https://unpkg.com/bootstrap-table@1.17.1/dist/extensions/export/bootstrap-table-export.min.js"></script>

</body>
</html>
