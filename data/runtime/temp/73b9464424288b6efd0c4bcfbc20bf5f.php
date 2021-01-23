<?php /*a:5:{s:82:"/Volumes/MMD/project/github/gxzbxh/public/themes/simpleboot3/zbxh/index/index.html";i:1611373389;s:85:"/Volumes/MMD/project/github/gxzbxh/public/themes/simpleboot3/zbxh/../public/head.html";i:1609907678;s:81:"/Volumes/MMD/project/github/gxzbxh/public/themes/simpleboot3/public/function.html";i:1586872333;s:84:"/Volumes/MMD/project/github/gxzbxh/public/themes/simpleboot3/zbxh/../public/nav.html";i:1609907115;s:87:"/Volumes/MMD/project/github/gxzbxh/public/themes/simpleboot3/zbxh/../public/footer.html";i:1602490153;}*/ ?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=1024"/>
    <meta name="apple-mobile-web-app-capable" content="yes"/>
    
<?php 
    /*可以加多个方法哟！*/
    if (!function_exists('_sp_helloworld')) {
        function _sp_helloworld(){
        echo "hello ThinkCMF!";
        }
    }

    if (!function_exists('_sp_helloworld2')) {
        function _sp_helloworld2(){
        echo "hello ThinkCMF2!";
        }
    }

    if (!function_exists('_sp_helloworld3')) {
        function _sp_helloworld3(){
        echo "hello ThinkCMF3!";
        }
    }
 ?>
<meta name="author" content="ThinkCMF">
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">

<!-- Set render engine for 360 browser -->
<meta name="renderer" content="webkit">

<!-- No Baidu Siteapp-->
<meta http-equiv="Cache-Control" content="no-siteapp"/>
<title><?php echo $site_info['site_name']; ?></title>
<meta name="keywords" content="广西珠宝,广西珠宝行业,广西珠宝协会">
<meta name="description" content="为“广西珠宝协会”，简称“桂宝协”，英文名称“Jewelry Association of GuangXi”缩写为“JAG”。是由广西珠宝玉石首饰行业的企业、相关机构及珠宝界的知名人士、珠宝爱好者自愿结成的广西全区性、行业性社会团体，是非营利性社会组织。">
<!-- HTML5 shim for IE8 support of HTML5 elements -->
<!--[if lt IE 9]>
<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
<![endif]-->
<link rel="icon" href="/themes/simpleboot3/public/assets/images/favicon.png" type="image/png">
<link rel="shortcut icon" href="/themes/simpleboot3/public/assets/images/favicon.png" type="image/png">

<!--[if IE 7]>
<link rel="stylesheet" href="/themes/simpleboot3/public/assets/simpleboot3/font-awesome/4.4.0/css/font-awesome-ie7.min.css">
<![endif]-->


<script type="text/javascript">
    //全局变量
    var GV = {
        ROOT: "/",
        WEB_ROOT: "/",
        JS_ROOT: "static/js/"
    };
</script>


<link rel="stylesheet" type="text/css" href="/themes/simpleboot3/zbxh/public/assets/css/css.css?v=001"/>
<script src="http://qiniu-jiaxiao.henbaoli.com/js/jquery-1.11.0.min.js" type="text/javascript"></script>
<script type="text/javascript" src="http://qiniu-jiaxiao.henbaoli.com/js/vue.js"></script>

<script src="/themes/simpleboot3/zbxh/public/assets/js/tab_dongt.min.js"  type="text/javascript"></script>




	
    <meta name="description" content=""/>
</head>
<body>
<div id="appnavs" class="haerh">
    <div class="logo">
        <div class="logo_left"><img src="/themes/simpleboot3/zbxh/public/assets/images/logo.png"></div>
        <div class="logo_right">
            <!--            <a href="javascript:void(0);" onclick="setHome(this,window.location)">设为首页</a> |-->
            <a href="javascript:void(0);">联系电话：0771-5719066</a> |
            <a href="javascript:void(0);" @mouseover="qrcode(1);" @mouseover="qrcode(1);"
               @mouseout="qrcode(0);">扫码关注公众号</a>
        </div>
        <div :style="{display: showqr == 0?'none':'block'}" class="cxianshiyc">
            <img src="/themes/simpleboot3/zbxh/public/assets/images/qrcode.jpg">
        </div>
    </div>


    <div class="navbj">
        <div class="nav">
            <ul class="topnav">
                <li :class="[{'moren':topcid==0},'']"><a href="/">网站首页</a></li>
                <li v-for="item in menu" :class="[{'moren':topcid==item.topmenu.id},'']"
                    @mouseover="conSonMenu($event,item.topmenu.id)" @mouseout="conSonMenuout()">
                    <a v-if="item.topmenu.is_hit == 1" :href="'/zbxh/index/postlist/cid/'+item.topmenu.id+'.html'">{{item.topmenu.name}}</a>
                    <a v-if="item.topmenu.is_hit == 2" href="javascript:void(0);">{{item.topmenu.name}}</a>
                </li>
            </ul>
        </div>
    </div>

    <div class="items" :style="{display: (isshow==0?'none':'block')}" @mouseover="conSonMenu($event,menutag)"
         @mouseout="conSonMenuout()">
        <div class="items_sub">
            <a v-for="item in sonmenu" :href="'/zbxh/index/postlist/cid/'+item.id+'.html'">{{item.name}}</a>
        </div>
    </div>

</div>


<script>
    new Vue({
        el: "#appnavs",
        data: {
            topcid: 0,
            menu: [],
            sonmenu: [],
            isshow: 0,
            menutag: 0,
            showqr: 0,
        },
        methods: {
            // ck(mx) {
            //   this.activeName = mx;
            // }
        },
        created() {
            var tag = "<?php echo $topcid; ?>";
            console.log(tag)
            if (tag !== null) {
                this.topcid = tag;
            }
            this.getMenu();
        },
        methods: {
            getMenu: function () {
                var _self = this;
                $.ajax({
                    type: 'get',
                    data: {},
                    url: "<?php echo url('/zbxh/index/getmenuajax'); ?>",
                    dataType: 'json',
                    success: function (rs) {
                        var tempmenulist = rs.data;
                        var finalmenu = [];
                        for (i = 0; i < tempmenulist.length; i++) {
                            var topmenu;
                            if (tempmenulist[i].parent_id == 0) {
                                topmenu = tempmenulist[i];
                                var temparra = [];
                                for (j = 0; j < tempmenulist.length; j++) {
                                    if (tempmenulist[j].parent_id == tempmenulist[i].id) {
                                        temparra.push(tempmenulist[j]);
                                    }
                                }
                                finalmenu.push({
                                    'topmenu': topmenu,
                                    'sonmenu': temparra
                                })
                            } else {
                                continue;
                            }
                        }
                        _self.menu = finalmenu;
                    }
                })
            },
            conSonMenu: function ($event, id) {
                for (i = 0; i < this.menu.length; i++) {
                    if (this.menu[i].topmenu.id == id) {
                        if (this.menu[i].sonmenu.length > 0) {
                            this.isshow = 1;
                            this.menutag = id;
                            this.sonmenu = this.menu[i].sonmenu;
                        } else {
                            this.isshow = 0;
                        }
                    }
                }

            },
            conSonMenuout: function () {
                this.isshow = 0;
            },
            qrcode: function (tag) {
                this.showqr = tag;
            }

        }
    })
</script>

<div id="index">
    <div class="bannert">
        <!--bannert_left-->
        <div class="bannert_left">
            <div class="index_slide" id="J_indexSlide">
                <div class="box" id="J_indexSlideBox">
                    <div class="show" style="display:block">
                        <a :href="'/zbxh/index/post/pid/'+onepic.id+'.html'"><img :src="onepic.thumbnail"/></a>
                        <div class="title">{{onepic.post_title}}</div>
                    </div>

                </div>
                <ul class="nav" id="J_indexSlideNav">
                    <li v-for="(item,key) in image" @mouseover="imgmover(item.id)"
                        :class="item.id == tagimgid?'cur':''">
                        <a href="#"><img :src="item.thumbnail"/></a>

                    </li>
                </ul>
            </div>
        </div>
        <!--bannert_left end-->

        <div class="bannert_right">
            <h3 class="titlest">
                热点关注
            </h3>
            <ul class="con_tuju">
                <?php if(is_array($alldata['hotdata']) || $alldata['hotdata'] instanceof \think\Collection || $alldata['hotdata'] instanceof \think\Paginator): if( count($alldata['hotdata'])==0 ) : echo "" ;else: foreach($alldata['hotdata'] as $key=>$vo): ?>
                    <li>
                        <?php if($key <= 2): ?>
                            <span class="bj"><?php echo $key+1; ?></span>
                            <?php else: ?>
                            <span class="bj1"><?php echo $key+1; ?></span>
                        <?php endif; ?>
                        <a :href="'/zbxh/index/post/pid/'+<?php echo $vo['post_id']; ?>+'.html'"><?php echo $vo['post_title']; ?></a>
                    </li>
                <?php endforeach; endif; else: echo "" ;endif; ?>
            </ul>
        </div>
    </div>

    <div class="main">
        <div class="main_one">
            <div class="main_one_l">
                <ul class="main_title">
                    <li class="morest"><a href="java:script:void(0);">资讯中心</a></li>
                    <?php if(is_array($alldata['zxzxmenu']) || $alldata['zxzxmenu'] instanceof \think\Collection || $alldata['zxzxmenu'] instanceof \think\Paginator): if( count($alldata['zxzxmenu'])==0 ) : echo "" ;else: foreach($alldata['zxzxmenu'] as $key=>$vo): ?>
                        <li><a
                                :href="'/zbxh/index/postlist/cid/'+<?php echo $vo['id']; ?>+'.html'"><?php echo $vo['name']; ?></a></li>
                    <?php endforeach; endif; else: echo "" ;endif; ?>
                </ul>

                <div class="main_sub">
                    <div class="main_sub_l">
                        <?php if(is_array($alldata['zxzx']['data']) || $alldata['zxzx']['data'] instanceof \think\Collection || $alldata['zxzx']['data'] instanceof \think\Paginator): if( count($alldata['zxzx']['data'])==0 ) : echo "" ;else: foreach($alldata['zxzx']['data'] as $key=>$vo): if($key == 0): ?>
                                <dl class="imgst">
                                    <dd>
                                        <a href="/zbxh/index/post/pid/<?php echo $vo['post_id']; ?>.html"><img
                                                src="<?php echo $vo['thumbnail']; ?>"></a>
                                    </dd>
                                    <dt><a :href="'/zbxh/index/post/pid/'+<?php echo $vo['post_id']; ?>+'.html'"><?php echo $vo['post_title']; ?></a>
                                    </dt>
                                </dl>
                            <?php endif; ?>
                        <?php endforeach; endif; else: echo "" ;endif; ?>
                        <ul class="imgst_foot imgst_footmar">
                            <?php if(is_array($alldata['zxzx']['data']) || $alldata['zxzx']['data'] instanceof \think\Collection || $alldata['zxzx']['data'] instanceof \think\Paginator): if( count($alldata['zxzx']['data'])==0 ) : echo "" ;else: foreach($alldata['zxzx']['data'] as $zxzx_tag=>$vo): if(($zxzx_tag <= 2)): if(($zxzx_tag != 0)): ?>
                                        <li class="fl">
                                            <a href="/zbxh/index/post/pid/<?php echo $vo['post_id']; ?>.html">
                                                <img src="<?php echo $vo['thumbnail']; ?>">
                                                <div class="tit"><?php echo $vo['post_title']; ?></div>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php endforeach; endif; else: echo "" ;endif; ?>
                        </ul>
                    </div>
                    <div class="main_sub_r">
                        <ul class="main_lists">
                            <?php if(is_array($alldata['zxzx']['data']) || $alldata['zxzx']['data'] instanceof \think\Collection || $alldata['zxzx']['data'] instanceof \think\Paginator): if( count($alldata['zxzx']['data'])==0 ) : echo "" ;else: foreach($alldata['zxzx']['data'] as $zxzx_tag=>$vo): ?>
                                <li v-if="<?php echo $zxzx_tag; ?> > 2">
                                    <a href="'/zbxh/index/post/pid/item.post_id.html">
                                        <h3><?php echo $vo['post_title']; ?></h3>
                                        <p><?php echo $vo['post_excerpt']; ?></p>
                                    </a>
                                </li>
                            <?php endforeach; endif; else: echo "" ;endif; ?>
                        </ul>
                    </div>
                </div>

            </div>
            <div class="main_one_r">
                <h3 class="titlest">
                    认识协会
                </h3>
                <ul class="side_groups">
                    <?php if(is_array($alldata['rsxhmenu']) || $alldata['rsxhmenu'] instanceof \think\Collection || $alldata['rsxhmenu'] instanceof \think\Paginator): if( count($alldata['rsxhmenu'])==0 ) : echo "" ;else: foreach($alldata['rsxhmenu'] as $key=>$vo): if($key == 0): ?>
                            <li>
                                <div class="fix">
                                    <div class="suanmen_img"><a href="#"><img id="targetImage" src="<?php echo $vo['more']; ?>"
                                                                              width="75" height="102"></a></div>
                                    <div class="cell">
                                        <a :href="'/zbxh/index/postlist/cid/'+<?php echo $vo['id']; ?>+'.html'" class="anrong">
                                            <h3><?php echo $vo['name']; ?></h3>
                                            <p><?php echo $vo['description']; ?></p>
                                        </a>
                                    </div>
                                </div>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; endif; else: echo "" ;endif; if(is_array($alldata['rsxhmenu']) || $alldata['rsxhmenu'] instanceof \think\Collection || $alldata['rsxhmenu'] instanceof \think\Paginator): if( count($alldata['rsxhmenu'])==0 ) : echo "" ;else: foreach($alldata['rsxhmenu'] as $key=>$vo): if($key != 0): ?>
                            <li class="bj_jkh"><a
                                    :href="'/zbxh/index/postlist/cid/'+<?php echo $vo['id']; ?>+'.html'"><img
                                    src="/themes/simpleboot3/zbxh/public/assets/images/25.png" width="10"> <?php echo $vo['name']; ?></a></li>
                        <?php endif; ?>
                    <?php endforeach; endif; else: echo "" ;endif; ?>
                </ul>
            </div>
        </div>


        <!--公告-->
        <div class="main_one">
            <div class="main_one_l">
                <div class="index_suer_float">
                    <h3 class="titlest">
                        <a :href="'/zbxh/index/postlist/cid/9.html'">更多</a> 协会动态
                    </h3>
                    <!--                    <ul class="main_lists">-->
                    <!--                        <li v-for="(item,key) in xhdtlist" class="notes mst16"><a-->
                    <!--                                :href="'/zbxh/index/post?pid='+item.post_id">{{item.post_title}}</a>-->
                    <!--                        </li>-->
                    <!--                    </ul>-->
                    <?php if(is_array($alldata['xhdtlist']) || $alldata['xhdtlist'] instanceof \think\Collection || $alldata['xhdtlist'] instanceof \think\Paginator): if( count($alldata['xhdtlist'])==0 ) : echo "" ;else: foreach($alldata['xhdtlist'] as $key=>$vo): if($key == 0): ?>
                            <dl class="imgst useryh_l">
                                <dd><a :href="'/zbxh/index/post/pid/'+<?php echo $vo['post_id']; ?>+'.html'"><img src="<?php echo $vo['thumbnail']; ?>"
                                                                                                  style="width:180px ; height: 192px;"></a>
                                </dd>
                                <dt class="titlstno"><a
                                        :href="'/zbxh/index/post/pid/'+<?php echo $vo['post_id']; ?>+'.html'"><?php echo $vo['post_title']; ?></a>
                                </dt>
                            </dl>
                        <?php endif; ?>
                    <?php endforeach; endif; else: echo "" ;endif; ?>
                    <div class="useryh_r">
                        <ul class="main_lists">
                            <?php if(is_array($alldata['xhdtlist']) || $alldata['xhdtlist'] instanceof \think\Collection || $alldata['xhdtlist'] instanceof \think\Paginator): if( count($alldata['xhdtlist'])==0 ) : echo "" ;else: foreach($alldata['xhdtlist'] as $key=>$vo): if($key != 0): ?>
                                    <li class="notes"><a
                                            :href="'/zbxh/index/post/pid/'+<?php echo $vo['post_id']; ?>+'.html'"><?php echo $vo['post_title']; ?></a>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; endif; else: echo "" ;endif; ?>
                        </ul>
                    </div>
                </div>

                <div class="index_suer_r">
                    <h3 class="titlest">
                        <a :href="'/zbxh/index/postlist/cid/5.html'">更多</a> 通知公告
                    </h3>
                    <!--                    <ul class="main_lists  ">-->
                    <!--                        <li v-for="(item,key) in tzgglist" class="notes mst16"><a-->
                    <!--                                :href="'/zbxh/index/post?pid='+item.post_id">{{item.post_title}}</a>-->
                    <!--                        </li>-->
                    <!--                    </ul>-->
                    <?php if(is_array($alldata['tzgglist']) || $alldata['tzgglist'] instanceof \think\Collection || $alldata['tzgglist'] instanceof \think\Paginator): if( count($alldata['tzgglist'])==0 ) : echo "" ;else: foreach($alldata['tzgglist'] as $key=>$vo): if($key == 0): ?>
                            <dl class="imgst useryh_l">
                                <dd><a :href="'/zbxh/index/post/pid/'+<?php echo $vo['post_id']; ?>+'.html'"><img src="<?php echo $vo['thumbnail']; ?>"
                                                                                                  style="width:180px ; height: 192px;"></a>
                                </dd>
                                <dt class="titlstno"><a
                                        :href="'/zbxh/index/post/pid/'+<?php echo $vo['post_id']; ?>+'.html'"><?php echo $vo['post_title']; ?></a>
                                </dt>
                            </dl>
                        <?php endif; ?>
                    <?php endforeach; endif; else: echo "" ;endif; ?>
                    <div class="useryh_r">
                        <ul class="main_lists">
                            <?php if(is_array($alldata['tzgglist']) || $alldata['tzgglist'] instanceof \think\Collection || $alldata['tzgglist'] instanceof \think\Paginator): if( count($alldata['tzgglist'])==0 ) : echo "" ;else: foreach($alldata['tzgglist'] as $key=>$vo): if($key != 0): ?>
                                    <li class="notes"><a
                                            :href="'/zbxh/index/post/pid/'+<?php echo $vo['post_id']; ?>+'.html'"><?php echo $vo['post_title']; ?></a>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; endif; else: echo "" ;endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="main_one_r">
                <h3 class="titlest">
                    <a :href="'/zbxh/index/postlist/cid/15.html'">更多</a> 会员活动
                </h3>
                <ul class="side_groups">
                    <?php if(is_array($alldata['hyhdlist']) || $alldata['hyhdlist'] instanceof \think\Collection || $alldata['hyhdlist'] instanceof \think\Paginator): if( count($alldata['hyhdlist'])==0 ) : echo "" ;else: foreach($alldata['hyhdlist'] as $key=>$vo): ?>
                        <li>
                            <div class="fix">
                                <div class="suanmen_img"><a :href="'/zbxh/index/post/pid/'+<?php echo $vo['post_id']; ?>+'.html'"><img
                                        id="targetImage" src="<?php echo $vo['thumbnail']; ?>"
                                        width="75" height="102"></a></div>
                                <div class="cell">
                                    <a :href="'/zbxh/index/post/pid/'+<?php echo $vo['post_id']; ?>+'.html'" class="anrong">
                                        <h3><?php echo $vo['post_title']; ?></h3>
                                        <p class="jieduan"><?php echo $vo['post_excerpt']; ?></p>
                                    </a>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; endif; else: echo "" ;endif; ?>
                </ul>
            </div>
        </div>

        <!--公告-->

        <!--会员-->
        <div class="main_one">
            <div class="main_one_l">
                <ul class="main_title">
                    <li class="morest"><a href="javascript:void(0);">会员风采</a></li>
                </ul>

                <div class="index_suer_float">
                    <h4 class="tisubt"><font><a :href="'/zbxh/index/postlist/cid/13.html'">更多</a></font>企业文化</h4>
                    <?php if(is_array($alldata['qywhlist']) || $alldata['qywhlist'] instanceof \think\Collection || $alldata['qywhlist'] instanceof \think\Paginator): if( count($alldata['qywhlist'])==0 ) : echo "" ;else: foreach($alldata['qywhlist'] as $key=>$vo): if($key == 0): ?>
                            <dl class="imgst useryh_l">
                                <dd><a :href="'/zbxh/index/post/pid/'+<?php echo $vo['post_id']; ?>+'.html'"><img src="<?php echo $vo['thumbnail']; ?>"
                                                                                                  style="width:180px ; height: 192px;"></a>
                                </dd>
                                <dt class="titlstno"><a
                                        :href="'/zbxh/index/post/pid/'+<?php echo $vo['post_id']; ?>+'.html'"><?php echo $vo['post_title']; ?></a>
                                </dt>
                            </dl>
                        <?php endif; ?>
                    <?php endforeach; endif; else: echo "" ;endif; ?>
                    <div class="useryh_r">
                        <ul class="main_lists">
                            <?php if(is_array($alldata['qywhlist']) || $alldata['qywhlist'] instanceof \think\Collection || $alldata['qywhlist'] instanceof \think\Paginator): if( count($alldata['qywhlist'])==0 ) : echo "" ;else: foreach($alldata['qywhlist'] as $key=>$vo): if($key != 0): ?>
                                    <li class="notes"><a
                                            :href="'/zbxh/index/post/pid/'+<?php echo $vo['post_id']; ?>+'.html'"><?php echo $vo['post_title']; ?></a>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; endif; else: echo "" ;endif; ?>
                        </ul>
                    </div>
                </div>

                <div class="index_suer_r">
                    <h4 class="tisubt"><font><a :href="'/zbxh/index/postlist/cid/14.html'">更多</a></font> 放心示范店</h4>
                    <?php if(is_array($alldata['fxsflist']) || $alldata['fxsflist'] instanceof \think\Collection || $alldata['fxsflist'] instanceof \think\Paginator): if( count($alldata['fxsflist'])==0 ) : echo "" ;else: foreach($alldata['fxsflist'] as $key=>$vo): if($key == 0): ?>
                            <dl class="imgst useryh_l">
                                <dd><a :href="'/zbxh/index/post/pid/'+<?php echo $vo['post_id']; ?>+'.html'"><img src="<?php echo $vo['thumbnail']; ?>"
                                                                                                  style="width:180px ; height: 192px;"></a>
                                </dd>
                                <dt class="titlstno"><a
                                        :href="'/zbxh/index/post/pid/'+<?php echo $vo['post_id']; ?>+'.html'"><?php echo $vo['post_title']; ?></a>
                                </dt>
                            </dl>
                        <?php endif; ?>
                    <?php endforeach; endif; else: echo "" ;endif; ?>
                    <div class="useryh_r">
                        <ul class="main_lists  ">
                            <?php if(is_array($alldata['fxsflist']) || $alldata['fxsflist'] instanceof \think\Collection || $alldata['fxsflist'] instanceof \think\Paginator): if( count($alldata['fxsflist'])==0 ) : echo "" ;else: foreach($alldata['fxsflist'] as $key=>$vo): if($key != 0): ?>
                                    <li class="notes"><a
                                            :href="'/zbxh/index/post/pid/'+<?php echo $vo['post_id']; ?>+'.html'"><?php echo $vo['post_title']; ?></a>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; endif; else: echo "" ;endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="main_one_r">
                <h3 class="titlest">
                    专业人才
                </h3>
                <ul class="side_groups">
                    <?php if(is_array($alldata['zyrcmenu']) || $alldata['zyrcmenu'] instanceof \think\Collection || $alldata['zyrcmenu'] instanceof \think\Paginator): if( count($alldata['zyrcmenu'])==0 ) : echo "" ;else: foreach($alldata['zyrcmenu'] as $key=>$vo): ?>
                        <li>
                            <div class="fix">
                                <div class="suanmen_img"><a href="#"><img src="<?php echo $vo['more']; ?>"
                                                                          width="75" height="102"></a></div>
                                <div class="cell">
                                    <a :href="'/zbxh/index/postlist/cid/'+<?php echo $vo['id']; ?>+'.html'" class="anrong">
                                        <h3><?php echo $vo['name']; ?></h3>
                                        <p class="ovhidec"><?php echo $vo['description']; ?></p>
                                    </a>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; endif; else: echo "" ;endif; ?>
                </ul>

                <h3 class="titlest" style="margin-top: 35px;">
                    协会会员
                </h3>
                <ul class="main_lists">
                    <li class="bj_jkh">
                        <a :href="'/zbxh/index/postlist/cid/27.html'"><img
                                src="/themes/simpleboot3/zbxh/public/assets/images/25.png"
                                width="10"> 入会指南 </a>
                    </li>
                    </li>
                </ul>


            </div>
        </div>
        <!--会员-->

        <div class="main_two">
            <ul class="main_title">
                <li class="morest"><a href="javascript:void(0);">会展活动</a></li>
                <a :href="'/zbxh/index/postlist/cid/3.html'" class="gengduo">更多</a>
            </ul>
            <div class="hdong">
                <div class="hdong_l">
                    <?php if(is_array($alldata['hzhdlist']) || $alldata['hzhdlist'] instanceof \think\Collection || $alldata['hzhdlist'] instanceof \think\Paginator): if( count($alldata['hzhdlist'])==0 ) : echo "" ;else: foreach($alldata['hzhdlist'] as $key=>$vo): if($key == 0): ?>
                            <dl class="imgst hdong_l_l">
                                <dd><a :href="'/zbxh/index/post/pid/'+<?php echo $vo['post_id']; ?>+'.html'"><img src="<?php echo $vo['thumbnail']; ?>"
                                                                                                  style="width: 240px ;"></a>
                                </dd>
                                <dt class="titlstno240"><a :href="'/zbxh/index/post/pid/'+<?php echo $vo['post_id']; ?>+'.html'"><?php echo $vo['post_title']; ?></a>
                                </dt>
                            </dl>
                        <?php endif; ?>
                    <?php endforeach; endif; else: echo "" ;endif; ?>
                    <div class="hdong_l_r">
                        <ul class="main_lists mtlist30 ">
                            <?php if(is_array($alldata['hzhdlist']) || $alldata['hzhdlist'] instanceof \think\Collection || $alldata['hzhdlist'] instanceof \think\Paginator): if( count($alldata['hzhdlist'])==0 ) : echo "" ;else: foreach($alldata['hzhdlist'] as $key=>$vo): if($key == 2): ?>
                                    <li>
                                        <a :href="'/zbxh/index/post/pid/'+<?php echo $vo['post_id']; ?>+'.html'">
                                            <h3><?php echo $vo['post_title']; ?></h3>
                                            <p><?php echo $vo['post_excerpt']; ?></p>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; endif; else: echo "" ;endif; if(is_array($alldata['hzhdlist']) || $alldata['hzhdlist'] instanceof \think\Collection || $alldata['hzhdlist'] instanceof \think\Paginator): if( count($alldata['hzhdlist'])==0 ) : echo "" ;else: foreach($alldata['hzhdlist'] as $key=>$vo): if($key == 3): ?>
                                    <li>
                                        <a :href="'/zbxh/index/post/pid/'+<?php echo $vo['post_id']; ?>+'.html'">
                                            <h3><?php echo $vo['post_title']; ?></h3>
                                            <p><?php echo $vo['post_excerpt']; ?></p>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; endif; else: echo "" ;endif; ?>
                        </ul>
                    </div>
                </div>
                <div class="hdong_r">
                    <?php if(is_array($alldata['hzhdlist']) || $alldata['hzhdlist'] instanceof \think\Collection || $alldata['hzhdlist'] instanceof \think\Paginator): if( count($alldata['hzhdlist'])==0 ) : echo "" ;else: foreach($alldata['hzhdlist'] as $key=>$vo): if($key == 1): ?>
                            <dl class="imgst hdong_l_l">
                                <dd><a :href="'/zbxh/index/post/pid/'+<?php echo $vo['post_id']; ?>+'.html'"><img src="<?php echo $vo['thumbnail']; ?>"
                                                                                                  style="width: 240px ;"></a>
                                </dd>
                                <dt class="titlstno240"><a :href="'/zbxh/index/post/pid/'+<?php echo $vo['post_id']; ?>+'.html'"><?php echo $vo['post_title']; ?></a>
                                </dt>
                            </dl>
                        <?php endif; ?>
                    <?php endforeach; endif; else: echo "" ;endif; ?>
                    <div class="hdong_l_r">
                        <ul class="main_lists mtlist30 ">
                            <?php if(is_array($alldata['hzhdlist']) || $alldata['hzhdlist'] instanceof \think\Collection || $alldata['hzhdlist'] instanceof \think\Paginator): if( count($alldata['hzhdlist'])==0 ) : echo "" ;else: foreach($alldata['hzhdlist'] as $key=>$vo): if($key == 4): ?>
                                    <li>
                                        <a :href="'/zbxh/index/post/pid/'+<?php echo $vo['post_id']; ?>+'.html'">
                                            <h3><?php echo $vo['post_title']; ?></h3>
                                            <p><?php echo $vo['post_excerpt']; ?></p>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; endif; else: echo "" ;endif; if(is_array($alldata['hzhdlist']) || $alldata['hzhdlist'] instanceof \think\Collection || $alldata['hzhdlist'] instanceof \think\Paginator): if( count($alldata['hzhdlist'])==0 ) : echo "" ;else: foreach($alldata['hzhdlist'] as $key=>$vo): if($key == 5): ?>
                                    <li>
                                        <a :href="'/zbxh/index/post/pid/'+<?php echo $vo['post_id']; ?>+'.html'">
                                            <h3><?php echo $vo['post_title']; ?></h3>
                                            <p><?php echo $vo['post_excerpt']; ?></p>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; endif; else: echo "" ;endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!--会员风采-->

        <!--会员风采-->

    </div>
</div>

<!--内容-->

 <!--footer-->
  <div class="footer">
 	 <div>版权所有©广西珠宝协会，All Rights Reserved，桂ICP备11005654号</div>
 	 <div>地址：南宁市兴宁区苏州路15号南方大厦4楼</div>
 	 <div>电话：0771-5719066 传真：0771-5719066 邮编:530022 电子邮箱：2510909513@qq.com</div>
 	 <div class="imagesty"><img src="/themes/simpleboot3/zbxh/public/assets/images/110.jpg"> <img src="/themes/simpleboot3/zbxh/public/assets/images/zx.jpg"></div>
  </div>
 <!--footer-->
 

 
<script>
</script>
<script>
    //青秀区
    var app = new Vue({
        el: '#index',
        data: {
            hotdata: [],
            image: [],
            onepic: Object,
            tagimgid: 0,
            zxzxmenu: [],
            zyrcmenu: [],
            zxzxlist: [],
            rsxhmenu: [],
            qywhlist: [],
            fxsflist: [],
            hyhdlist: [],
            hzhdlist: [],
            xhdtlist: [],
            tzgglist: [],
        },
        created: function () {
            this.getalldata();
        },
        methods: {
            getalldata: function () {
                var _self = this;
                $.ajax({
                    type: 'get',
                    data: {
                        zxzxmid: 1,
                        hotlimit: 10,
                        imglimit: 3,
                        zxzxcid: 8,
                        zxzxlimit: 7,
                        rsxhmid: 4,
                        qywhcid: 13,
                        qywhlimit: 5,
                        fxsfcid: 14,
                        fxsflimit: 5,
                        hyhdcid: 15,
                        hyhdlimit: 2,
                        hzhdcid: 3,
                        hzhdlimit: 6,
                        xhdtcid: 9,
                        xhdtlimit: 5,
                        tzggcid: 5,
                        tzgglimit: 5,
                        zyrcmid: 21,
                    },
                    url: "<?php echo url('/zbxh/index/getAlldate'); ?>",
                    dataType: 'json',
                    success: function (rs) {
                        console.log(rs);
                        if (rs.code == -1) {
                            // _self.hotdata = rs.data.hotdata.data;
                            _self.image = rs.data.image.data;
                            _self.onepic = _self.image[0]
                            _self.tagimgid = _self.image[0].id;
                            // _self.zxzxmenu = rs.data.zxzxmenu;
                            // _self.zxzxlist = rs.data.zxzx.data;
                            // _self.rsxhmenu = rs.data.rsxhmenu;
                            // _self.qywhlist = rs.data.qywhlist.data;
                            // _self.fxsflist = rs.data.fxsflist.data;
                            // _self.hyhdlist = rs.data.hyhdlist.data;
                            // _self.hzhdlist = rs.data.hzhdlist.data;
                            // _self.xhdtlist = rs.data.xhdtlist.data;
                            // _self.tzgglist = rs.data.tzgglist.data;
                            // _self.zyrcmenu = rs.data.zyrcmenu;
                        }
                    }
                })
            },
            getZyrcMenu: function () {
                var _self = this;
                $.ajax({
                    type: 'get',
                    data: {mid: 21},
                    url: "<?php echo url('/zbxh/index/getMenuAjax'); ?>",
                    dataType: 'json',
                    success: function (rs) {
                        if (rs.code == -1) {
                            _self.zyrcmenu = rs.data;
                            console.log(_self.zyrcmenu);
                        }
                    }
                })
            },
            imgmover: function (id) {
                for (i = 0; i < this.image.length; i++) {
                    if (this.image[i].id == id) {
                        this.onepic = this.image[i];
                        this.tagimgid = this.image[i].id;
                    }
                }
            },
        }
    })
</script>
</body>
</html>

