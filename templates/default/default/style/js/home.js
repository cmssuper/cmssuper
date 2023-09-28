$(function() {
    //获取要定位元素距离浏览器顶部的距离
    var navT = $(".ta").offset().top;
    var navH = $(".ta").height();
    var conH = $(".page_arclist").height();
    //alert(bheight+':'+navH);
    //滚动条事件
    $(window).scroll(function() {
        //获取滚动条的滑动距离
        var scroT = $(this).scrollTop();
        //浏览器高度
        if (!$.support.leadingWhitespace) {
            bheight = document.compatMode == "CSS1Compat" ? document.documentElement.clientHeight : document.body.clientHeight;
        } else {
            bheight = self.innerHeight;
        }
        //滚动条的滑动距离大于等于定位元素距离浏览器顶部的距离，就固定，反之就不固定
        if (scroT >= navT - 0 && navH + 70 < bheight) {
            if (navH > conH) {
                $(".page_arclist").height(navH);
            }
            $(".ta").css({ "position": "fixed", "top": 0 });
        } else {
            $(".ta").css({ "position": "static" });
        }
    })
})