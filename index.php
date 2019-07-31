<?php
    //비공개 자료 출력 여부
    function GetPublicYN()
    {
        global $privateKey, $privateValue;

        $public_yn = "Y";

        if(Request(strtolower($privateKey)) == $privateValue || Request(strtoupper($privateKey)) == $privateValue)
        {
            $public_yn = "";
        }

        return $public_yn;
    }

    function Get($node, $childName)
    {
        return $node->getElementsByTagName($childName)->item(0)->nodeValue;
    }

    function Request($key)
    {
        $result = $_GET[$key];
        if($result == "")
        {
            $result = $_POST[$key];
        }

        return $result;
    }

    function BackupData($fileName)
    {
        $directoryName = "backup";
        if(!is_dir($directoryName)){
            mkdir($directoryName, 0755);
        }
        $timeStamp = (strtotime((new DateTime())->format(DateTime::W3C))*1000);
        copy($fileName, "backup/{$timeStamp}_{$fileName}");
    }


    $jsonConfig = new stdClass();
    $privateKey = "";
    $privateValue = "";
    
    $configFileName = "MobileStartPage.config";
    $handle = @fopen($configFileName,'r');
    if($handle !== false){
        $jsonConfig = json_decode(file_get_contents($configFileName));
        $privateKey = $jsonConfig->privateKey;
        $privateValue = $jsonConfig->privateValue;
    }
    

    $privatePair = "";
    if(GetPublicYN() == "")
    {
        $privatePair = "&" . $privateKey . "=" . $privateValue;
    }


    //ajax 요청이 있는 경우 처리하고 종료
    $ajaxMode = Request("AJAX_MODE");
    if($ajaxMode != "")
    {
        header("Content-Type: application/json");


        $jsonFileName =  "MobileStartPage.json";
        $strJsonFileContents = file_get_contents($jsonFileName);
        $jsonData = json_decode($strJsonFileContents);


        $flagSave = false;
        $result = "";


        if($ajaxMode == "ADD_CATEGORY")
        {
            //
        }
        elseif($ajaxMode == "REMOVE_CATEGORY")
        {
            //
        }
        elseif($ajaxMode == "MOD_CATEGORY")
        {
            //
        }
        elseif($ajaxMode == "GET_DATA")
        {
            //카테고리 필터링
            $jsonData->category = array_filter($jsonData->category, function($category){
                if($category->use_yn == "Y")
                {
                    if($category->public_yn == GetPublicYN() || GetPublicYN() == "")
                    {
                        return $category;
                    }
                }
            });

            //사이트 필터링
            $jsonData->site = array_filter($jsonData->site, function($site) use($jsonData){
                if($site->use_yn == "Y")
                {
                    $arrTmp = array_filter($jsonData->category, function($category) use($site){
                        if($category->category_idx == $site->category_idx)
                        {
                            return $category;
                        }
                    });

                    if(count($arrTmp) > 0)
                    {
                        return $site;
                    }
                }
            });

            $result = json_encode($jsonData, JSON_UNESCAPED_UNICODE);
        }
        elseif($ajaxMode == "XML_TO_JSON")
        {
            $xmlFileName = "MobileStartPage.xml";
            $xmlDoc = new DOMDocument();
            $xmlDoc->load($xmlFileName);


            //xml데이터 카테고리, 사이트로 분류
            $categoryNodes = $xmlDoc->getElementsByTagName("MENU_CATEGORY_INFO");//array();
            $siteNodes = $xmlDoc->getElementsByTagName("MENU_SITE_INFO");//array();

            $data["category"] = array();
            $data["site"] = array();
            foreach($categoryNodes as $category)
            {
                $objCategory = new stdClass();
                $objCategory->category_idx = Get($category, "CATEGORY_IDX");
                $objCategory->category_name = Get($category, "CATEGORY_NAME");
                $objCategory->category_sort = Get($category, "CATEGORY_SORT");
                $objCategory->public_yn = Get($category, "PUBLIC_YN");
                $objCategory->use_yn = Get($category, "USE_YN");
                $objCategory->create_date = strtotime(Get($category, "CREATE_DATE"));
                $objCategory->update_date = strtotime(Get($category, "UPDATE_DATE"));

                array_push($data["category"], $objCategory);
            }

            foreach($siteNodes as $site)
            {
                $objSite = new stdClass();
                $objSite->site_idx = Get($site, "SITE_IDX");
                $objSite->site_name = Get($site, "SITE_NAME");
                $objSite->site_url = Get($site, "SITE_URL");
                $objSite->site_url_mobile = Get($site, "SITE_URL_MOBILE");
                $objSite->site_sort = Get($site, "SITE_SORT");
                $objSite->use_yn = Get($site, "USE_YN");
                $objSite->create_date = strtotime(Get($site, "CREATE_DATE"));
                $objSite->update_date = strtotime(Get($site, "UPDATE_DATE"));
                $objSite->category_idx = Get($site, "CATEGORY_IDX");

                array_push($data["site"], $objSite);
            }

            
            var_dump($data);

            $fp = fopen($jsonFileName, 'w');
            fwrite($fp, json_encode($data, JSON_UNESCAPED_UNICODE));
            fclose($fp);
        }
        elseif($ajaxMode == "GET_SITE_INFO" && GetPublicYN() != "Y")
        {
            $site_idx = Request("SITE_IDX");
            if($site_idx > 0)
            {
                $arrSite = array_filter($jsonData->site, function($site) use($site_idx){
                    if($site->site_idx == $site_idx)
                    {
                        return $site;
                    }
                });
                $arrSite = array_values($arrSite);

                if(count($arrSite) > 0)
                {
                    $site = $arrSite[0];
                    $arrCategory = array_filter($jsonData->category, function($category) use($site){
                        if($category->category_idx == $site->category_idx)
                        {
                            return $category;
                        }
                    });
                    $arrCategory = array_values($arrCategory);

                    if(count($arrCategory) > 0)
                    {
                        $result = json_encode($site, JSON_UNESCAPED_UNICODE);
                    }
                }
            }
        }
        elseif($ajaxMode == "SAVE_SITE_INFO" && GetPublicYN() != "Y")
        {
            $site_idx = Request("txtSiteIdx");
            $category_idx = Request("ddlCategory");
            $site_name = Request("txtSiteName");
            $site_url = Request("txtSiteUrl");
            $site_url_mobile = Request("txtSiteUrlMobile");
            $site_sort = Request("txtSiteSort");
            $use_yn = Request("ddlUseYN");
            $jsTimeStamp = strtotime((new DateTime())->format(DateTime::W3C))*1000;

            if($category_idx > 0)
            {
                $flagSave = true;
                
                if($site_idx <= 0)
                {
                    //신규
                    $newSiteIdx = 0;
                    foreach($jsonData->site as $site)
                    {
                        if($site->site_idx > $newSiteIdx)
                        {
                            $newSiteIdx = $site->site_idx;
                        }
                    }
                    $newSiteIdx = $newSiteIdx + 1;


                    $newSite = new stdClass();
                    $newSite->site_idx = $newSiteIdx;
                    $newSite->site_name = $site_name;
                    $newSite->site_url = $site_url;
                    $newSite->site_url_mobile = $site_url_mobile;
                    $newSite->site_sort = $site_sort;
                    $newSite->use_yn = $use_yn;
                    $newSite->create_date = $jsTimeStamp;
                    $newSite->update_date = $jsTimeStamp;
                    $newSite->category_idx = $category_idx;

                    array_push($jsonData->site, $newSite);
                }
                else
                {
                    foreach($jsonData->site as $site)
                    {
                        if($site->site_idx == $site_idx)
                        {
                            $site->category_idx = $category_idx;
                            $site->site_name = $site_name;
                            $site->site_url = $site_url;
                            $site->site_url_mobile = $site_url_mobile;
                            $site->site_sort = $site_sort;
                            $site->use_yn = $use_yn;
                            $site->update_date = $jsTimeStamp;
                            break;
                        }
                    }
                }
            }
        }


        if($flagSave == true)
        {
            BackupData($jsonFileName);


            $fp = fopen($jsonFileName, 'w');
            fwrite($fp, json_encode($jsonData, JSON_UNESCAPED_UNICODE));
            fclose($fp);

            if($result == "")
            {
                $result = json_encode("");
            }
        }


        ob_clean();
        echo $result;
        return;
    }
?>

<!DOCTYPE HTML PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.2//EN" "http://www.wapforum.org/DTD/xhtml-mobile12.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ko" xml:lang="ko">
<head>
    <meta http-equiv="Content-Type" content="text/html" />
    <meta name="viewport" content="user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, width=device-width" />
    <title>Mobile Start Page</title>
    <link href="/css/MobileStartPage.css" rel="stylesheet" />
    <style>
        ul {
        display:none;
        }
    </style>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <!-- <script src="/js/jquery-1.6.4.js"></script> -->
    <script src="/js/common.js"></script>
    <script src="/js/mobileStartPage.js"></script>
</head>
<body>
    <div id="divMain">
        <div id="divNewTab">
            <span id="newTab">새탭 생성</span>
        </div>
        <div id="divSearch">
            <input id="txtSearchWord" type="text" style="width:90px" />
        </div>
        <!--북마크섹션 시작-->
        <input id="hdfCatIdx" type="hidden" />
        <input id="hdfPrivatePair" type="hidden" value="<?php echo $privatePair ?>" />
        <div class="mainCategory">
        </div>
        <!--북마크섹션 끝-->
    </div>
    <div id="divCategory">
        <span class="title">이름</span><input id="txtCategoryName" type="text" class="textbox" />
        <span class="title">순서</span><input id="txtCategorySort" type="text" class="textbox" />
    </div>
    <div id="divEditSite">
        <table style="width:100%;">
            <tr>
                <td style="width:140px">
                    <span class="title">카테고리</span>
                </td>
                <td>
                    <select name="ddlCategory" id="ddlCategory">
                    </select>
                </td>
            </tr>
            <tr>
                <td><span class="title">ID</span></td>
                <td><input id="txtSiteIdx" type="text" class="textbox disabled" readonly="readonly" /></td>
            </tr>
            <tr>
                <td><span class="title">이름</span></td>
                <td><input id="txtSiteName" type="text" class="textbox" /></td>
            </tr>
            <tr>
                <td><span class="title">URL</span></td>
                <td><input id="txtSiteUrl" type="text" class="textbox" /></td>
            </tr>
            <tr>
                <td><span class="title">URL MOBILE</span></td>
                <td><input id="txtSiteUrlMobile" type="text" class="textbox" /></td>
            </tr>
            <tr>
                <td><span class="title">순서</span></td>
                <td><input id="txtSiteSort" type="text" class="textbox" /></td>
            </tr>
            <tr>
                <td><span class="title">사용</span></td>
                <td>
                    <select id="ddlUseYN">
                        <option value="Y" selected="selected">사용</option>
                        <option value="N">사용안함</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td></td>
                <td style="text-align:right">
                    <span id="btnNew" style="cursor:pointer;">[신규]</span>
                    <span id="btnSave" style="cursor:pointer;">[저장]</span>
                    <span id="btnClose" style="cursor:pointer;">[닫기]</span>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>