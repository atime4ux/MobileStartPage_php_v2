$(document).ready(function () {
    GetData();    
});

function InitControl() {
    $("#txtSearchWord").keydown(function (key) {
        if (key.keyCode == 13) {
            var search_word = $("#txtSearchWord").val();
            $("#txtSearchWord").val('');
            SearchGoogle(search_word);
        }
    });

    $('#newTab').click(function () {
        NewTab();
    });

    $('.subCategory .title').click(function (evt) {
        const $elem = $(evt.currentTarget)
        ToggleDisplayProp($elem.data('categoryIdx'))
    });

    $('.subCategory .site').click(function (evt) {
        const $elem = $(evt.currentTarget)
        MovePage($elem.data('siteUrlMobile'), $elem.data('siteUrl'))
    });

    $('.subCategory .mod_site').click(function (evt) {
        const $elem = $(evt.currentTarget)
        GetSiteInfo($elem.data('siteIdx'))
    });

    $('#btnNew').click(function () {
        AddSiteInfo()
    })
    $('#btnSave').click(function () {
        SaveSiteInfo()
    })
    $('#btnClose').click(function () {
        HideEditSiteArea()
    })

    $("#hdfCatIdx").val($(".subCategory:first").find('.title').data('categoryIdx'));
}

function BindCategoryDdl(lstCategory){
    lstCategory.forEach((category)=>{
        $('#ddlCategory').append(`<option value='${category.category_idx}'>${category.category_name}</option>`)
    })
}

function GetData() {
    var url = window.location.pathname;
    var postdata = GetPostData() + "&AJAX_MODE=GET_DATA" + $("#hdfPrivatePair").val();

    $.ajax({
        type: "GET",
        cache: false,
        async: true,
        data: "json",
        url: url,
        data: postdata,
        success: function (data) {
            //console.log(data);
            DrawPage(data);
            InitControl();
            BindCategoryDdl(data.category);
        },
        error: function (data) {
            console.log("error", data);
        }
    });
}


function DrawPage(data) {
    const $mainCategory = $('.mainCategory')
    const lstCategory = data.category.sort((a, b) => { return a.category_sort - b.category_sort })
    const publicYn = data.category.filter(x => x.public_yn != 'Y').length > 0 ? '' : 'Y'

    for (i = 0; i < lstCategory.length; i++) {
        const category = lstCategory[i]

        const $subCategory = $('<div></div>')
            .addClass('subCategory')
            .append(`<span class='title' data-category-idx='${category.category_idx}'>${category.category_name}</span>`)
            .append(`<ul id='cat_${category.category_idx}' ${i == 0 ? `style='display:block;'` : ''}></ul>`)
        $mainCategory.append($subCategory)

        if (Array.isArray(data.site) == false) {
            console.log('try convert object to array')
            // array로 반환안되는 경우...왜??
            data.site = Object.keys(data.site).map(key => {
                return data.site[key]
            })
        }
        const lstSite = data.site.filter(x => x.category_idx == category.category_idx).sort((a, b) => { return a.site_sort - b.site_sort })
        for (j = 0; j < lstSite.length; j++) {
            const site = lstSite[j]
            const $liSite = $('<li></li>')
                .append(`<span class='site' id='site_${site.site_idx}' data-site-url-mobile='${site.site_url_mobile}' data-site-url='${site.site_url}' style='margin-right:20px'>${site.site_name}</span>`)
            if (publicYn == '') {
                $liSite.append(`<span class='mod_site' style='cursor:pointer; margin-right:20px;' data-site-idx='${site.site_idx}' >[수정]</span>`)
            }

            $subCategory.find('ul').append($liSite)
        }
    }
}


function ExpandCategory(categoryIdx) {
    $("#hdfCatIdx").val(categoryIdx);
    $(`#cat_${categoryIdx}`).show(200, function () {
        var offsetTop = ($(`#cat_${categoryIdx}`).offset().top * 1) - 30;
        $('html, body').animate({
            scrollTop: offsetTop
        }, 200);
    });
}
function ToggleDisplayProp(categoryIdx) {
    var cat_idx_prev = $("#hdfCatIdx").val();

    if (cat_idx_prev == '' || $(`#cat_${categoryIdx}`).length == 0) {
        ExpandCategory(categoryIdx);
    }
    else {
        if (categoryIdx == cat_idx_prev) {
            $(`#cat_${cat_idx_prev}`).hide();
            $("#hdfCatIdx").val('');
        }
        else {
            $(`#cat_${cat_idx_prev}`).hide();
            ExpandCategory(categoryIdx);
        }
    }
}
function IsMobile() {
    var filter = "win16|win32|win64|mac";

    if (navigator.platform) {
        if (filter.indexOf(navigator.platform.toLowerCase()) >= 0) {
            //데스크탑
            return false;
        }
    }

    return true;
}
function MovePage(mobileUrl, desktopUrl) {
    if (IsMobile() == true) {
        //window.open(mobileUrl);//크롬에 홈버튼 보이면서 그냥 페이지 이동으로 변경
        document.location.href = mobileUrl;
    }
    else {
        if (desktopUrl == undefined || desktopUrl == null || desktopUrl == '') {
            desktopUrl = mobileUrl;
        }
        document.location.href = desktopUrl;
    }
}
function NewTab() {
    window.open('about:blank');
}
function ShowEditSiteArea() {
    $("#divEditSite").css('display', 'block');
}
function HideEditSiteArea() {
    $("#divEditSite").css('display', 'none');
}
function AddSiteInfo(category_id) {
    $("#txtSiteIdx").val('');
    $("#txtSiteName").val('');
    $("#txtSiteUrl").val('');
    $("#txtSiteUrlMobile").val('');
    $("#txtSiteSort").val('');
    $("#ddlUseYN").val('Y');
    $("#ddlUseYN").attr('disabled', 'disabled');
}
function GetSiteInfo(site_idx) {
    ShowEditSiteArea();
    $("#ddlUseYN").removeAttr('disabled');

    var url = window.location.pathname;
    var postdata = "&SITE_IDX=" + site_idx
        + "&AJAX_MODE=GET_SITE_INFO" + $("#hdfPrivatePair").val();

    CallAjax(url, postdata, {
        Run: function (data) {
            if (data.site_idx != undefined && data.site_idx != null && site_idx != '') {
                $("#ddlCategory").val(data.category_idx);
                $("#txtSiteIdx").val(data.site_idx);
                $("#txtSiteName").val(data.site_name);
                $("#txtSiteUrl").val(data.site_url);
                $("#txtSiteUrlMobile").val(data.site_url_mobile);
                $("#txtSiteSort").val(data.site_sort);
            }
        }
    }
        , Ajax_Fail);
}
function SaveSiteInfo() {
    var url = window.location.pathname;
    var postdata = GetPostDataBody()
    postdata['AJAX_MODE'] = 'SAVE_SITE_INFO'

    if($("#hdfPrivatePair").val().length > 0)
    {
        const key = $("#hdfPrivatePair").val().split('&')[1].split('=')[0]
        const val = $("#hdfPrivatePair").val().split('&')[1].split('=')[1]
        postdata[key] = val
    }

    CallAjaxPost(url, postdata, {
        Run: function (data) {
            if (data == '') {
                window.location.reload(true);
            }
            else {
                Ajax_Fail.Run(data);
            }
        }
    }
        , Ajax_Fail);
}
function SearchGoogle(search_word) {
    var url = "http://www.google.co.kr/cse";
    var param = "?q=" + encodeURIComponent(search_word);

    window.open(url + param);
}
var AddSite_Success = {
    Run: function (data) {
    }
}