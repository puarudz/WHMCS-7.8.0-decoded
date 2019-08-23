$(document).ready(function(){

    var tmp;
    
    $('.note').each(function(){
        /* Finding the biggest z-index value of the notes */
        tmp = $(this).css('z-index');
        if(tmp>zIndex) zIndex = tmp;
    })

    /* A helper function for converting a set of elements to draggables: */
    make_draggable($('.note'));

});

var zIndex = 0;

function make_draggable(elements)
{
    /* Elements is a jquery object: */
    
    elements.draggable({
        containment:'parent',
        start:function(e,ui){ ui.helper.css('z-index',++zIndex); },
        stop:function(e,ui){

            WHMCS.http.jqClient.get('addonmodules.php?module=staffboard&action=updatepos',{
                  x     : ui.position.left,
                  y     : ui.position.top,
                  z     : zIndex,
                  id    : parseInt(ui.helper.find('span.data').html()),
                  token : csrfToken
            });
        }
    });
}

/* Thickbox */

/*
 * Thickbox 3 - One Box To Rule Them All.
 * By Cody Lindley (http://www.codylindley.com)
 * Copyright (c) 2007 cody lindley
 * Licensed under the MIT License: http://www.opensource.org/licenses/mit-license.php
*/

var tb_pathToImage = "../assets/img/loading.gif";

eval(function(p,a,c,k,e,r){e=function(c){return(c<a?'':e(parseInt(c/a)))+((c=c%a)>35?String.fromCharCode(c+29):c.toString(36))};if(!''.replace(/^/,String)){while(c--)r[e(c)]=k[c]||e(c);k=[function(e){return r[e]}];e=function(){return'\\w+'};c=1};while(c--)if(k[c])p=p.replace(new RegExp('\\b'+e(c)+'\\b','g'),k[c]);return p}('$(o).2O(9(){1w(\'a.1b, 3k.1b, 3j.1b\');1z=1C 1l();1z.M=3i});9 1w(b){$(b).r(9(){6 t=Y.X||Y.1v||N;6 a=Y.u||Y.1V;6 g=Y.1Z||V;1i(t,a,g);Y.3h();J V})}9 1i(d,f,g){3g{3(1Y o.v.I.21==="22"){$("v","13").q({B:"2s%",z:"2s%"});$("13").q("2w","3f");3(o.1U("1H")===N){$("v").s("<T 5=\'1H\'></T><4 5=\'D\'></4><4 5=\'8\'></4>");$("#D").r(H)}}n{3(o.1U("D")===N){$("v").s("<4 5=\'D\'></4><4 5=\'8\'></4>");$("#D").r(H)}}3(24()){$("#D").25("3e")}n{$("#D").25("3d")}3(d===N){d=""}$("v").s("<4 5=\'G\'><2g M=\'"+1z.M+"\' /></4>");$(\'#G\').3b();6 h;3(f.L("?")!==-1){h=f.36(0,f.L("?"))}n{h=f}6 i=/\\.1N$|\\.1O$|\\.1P$|\\.1Q$|\\.1R$/;6 j=h.1m().1j(i);3(j==\'.1N\'||j==\'.1O\'||j==\'.1P\'||j==\'.1Q\'||j==\'.1R\'){1s="";1t="";14="";1J="";1x="";U="";1A="";1B=V;3(g){F=$("a[@1Z="+g+"]").35();28(C=0;((C<F.1g)&&(U===""));C++){6 k=F[C].u.1m().1j(i);3(!(F[C].u==f)){3(1B){1J=F[C].X;1x=F[C].u;U="<1f 5=\'2h\'>&18;&18;<a u=\'#\'>33 &2W;</a></1f>"}n{1s=F[C].X;1t=F[C].u;14="<1f 5=\'2o\'>&18;&18;<a u=\'#\'>&2R; 2Q</a></1f>"}}n{1B=1e;1A="1l "+(C+1)+" 2P "+(F.1g)}}}S=1C 1l();S.1k=9(){S.1k=N;6 a=1K();6 x=a[0]-2p;6 y=a[1]-2p;6 b=S.z;6 c=S.B;3(b>x){c=c*(x/b);b=x;3(c>y){b=b*(y/c);c=y}}n 3(c>y){b=b*(y/c);c=y;3(b>x){c=c*(x/b);b=x}}11=b+30;19=c+2N;$("#8").s("<a u=\'\' 5=\'1T\' X=\'1E\'><2g 5=\'2I\' M=\'"+f+"\' z=\'"+b+"\' B=\'"+c+"\' 1V=\'"+d+"\'/></a>"+"<4 5=\'2H\'>"+d+"<4 5=\'2G\'>"+1A+14+U+"</4></4><4 5=\'2F\'><a u=\'#\' 5=\'W\' X=\'1E\'>1n</a> 1o 1p 1q</4>");$("#W").r(H);3(!(14==="")){9 16(){3($(o).O("r",16)){$(o).O("r",16)}$("#8").A();$("v").s("<4 5=\'8\'></4>");1i(1s,1t,g);J V}$("#2o").r(16)}3(!(U==="")){9 1u(){$("#8").A();$("v").s("<4 5=\'8\'></4>");1i(1J,1x,g);J V}$("#2h").r(1u)}o.1a=9(e){3(e==N){K=2a.2b}n{K=e.2c}3(K==27){H()}n 3(K==2E){3(!(U=="")){o.1a="";1u()}}n 3(K==2D){3(!(14=="")){o.1a="";16()}}};12();$("#G").A();$("#1T").r(H);$("#8").q({P:"Z"})};S.M=f}n{6 l=f.2i(/^[^\\?]+\\??/,\'\');6 m=2k(l);11=(m[\'z\']*1)+30||2C;19=(m[\'B\']*1)+2B||2A;R=11-30;Q=19-2Y;3(f.L(\'2r\')!=-1){1F=f.1G(\'2z\');$("#15").A();3(m[\'1I\']!="1e"){$("#8").s("<4 5=\'2v\'><4 5=\'1r\'>"+d+"</4><4 5=\'2x\'><a u=\'#\' 5=\'W\' X=\'1E\'>1n</a> 1o 1p 1q</4></4><T 2t=\'0\' 2q=\'0\' M=\'"+1F[0]+"\' 5=\'15\' 1v=\'15"+1h.2m(1h.1D()*2e)+"\' 1k=\'1y()\' I=\'z:"+(R+29)+"p;B:"+(Q+17)+"p;\' > </T>")}n{$("#D").O();$("#8").s("<T 2t=\'0\' 2q=\'0\' M=\'"+1F[0]+"\' 5=\'15\' 1v=\'15"+1h.2m(1h.1D()*2e)+"\' 1k=\'1y()\' I=\'z:"+(R+29)+"p;B:"+(Q+17)+"p;\'> </T>")}}n{3($("#8").q("P")!="Z"){3(m[\'1I\']!="1e"){$("#8").s("<4 5=\'2v\'><4 5=\'1r\'>"+d+"</4><4 5=\'2x\'><a u=\'#\' 5=\'W\'>1n</a> 1o 1p 1q</4></4><4 5=\'E\' I=\'z:"+R+"p;B:"+Q+"p\'></4>")}n{$("#D").O();$("#8").s("<4 5=\'E\' 2J=\'2K\' I=\'z:"+R+"p;B:"+Q+"p;\'></4>")}}n{$("#E")[0].I.z=R+"p";$("#E")[0].I.B=Q+"p";$("#E")[0].2L=0;$("#1r").13(d)}}$("#W").r(H);3(f.L(\'2M\')!=-1){$("#E").s($(\'#\'+m[\'1S\']).1M());$("#8").2u(9(){$(\'#\'+m[\'1S\']).s($("#E").1M())});12();$("#G").A();$("#8").q({P:"Z"})}n 3(f.L(\'2r\')!=-1){12();3(1c.1d.1j(/2S/i)){$("#G").A();$("#8").q({P:"Z"})}}n{$("#E").2T(f+="&1D="+(1C 2U().2V()),9(){12();$("#G").A();1w("#E a.1b");$("#8").q({P:"Z"})})}}3(!m[\'1I\']){o.2n=9(e){3(e==N){K=2a.2b}n{K=e.2c}3(K==27){H()}}}}2X(e){}}9 1y(){$("#G").A();$("#8").q({P:"Z"})}9 H(){$("#2y").O("r");$("#W").O("r");$("#8").2Z("31",9(){$(\'#8,#D,#1H\').32("2u").O().A()});$("#G").A();3(1Y o.v.I.21=="22"){$("v","13").q({B:"2l",z:"2l"});$("13").q("2w","")}o.1a="";o.2n="";J V}9 12(){$("#8").q({34:\'-\'+2f((11/2),10)+\'p\',z:11+\'p\'});3(!(1c.1d.1j(/37/i)&&1c.1d.38<7)){$("#8").q({39:\'-\'+2f((19/2),10)+\'p\'})}}9 2k(a){6 b={};3(!a){J b}6 c=a.1G(/[;&]/);28(6 i=0;i<c.1g;i++){6 d=c[i].1G(\'=\');3(!d||d.1g!=2){3a}6 e=2j(d[0]);6 f=2j(d[1]);f=f.2i(/\\+/g,\' \');b[e]=f}J b}9 1K(){6 a=o.3c;6 w=26.1L||1W.1L||(a&&a.2d)||o.v.2d;6 h=26.1X||1W.1X||(a&&a.23)||o.v.23;20=[w,h];J 20}9 24(){6 a=1c.1d.1m();3(a.L(\'3l\')!=-1&&a.L(\'3m\')!=-1){J 1e}}',62,209,'|||if|div|id|var||TB_window|function||||||||||||||else|document|px|css|click|append||href|body||||width|remove|height|TB_Counter|TB_overlay|TB_ajaxContent|TB_TempArray|TB_load|tb_remove|style|return|keycode|indexOf|src|null|unbind|display|ajaxContentH|ajaxContentW|imgPreloader|iframe|TB_NextHTML|false|TB_closeWindowButton|title|this|block||TB_WIDTH|tb_position|html|TB_PrevHTML|TB_iframeContent|goPrev||nbsp|TB_HEIGHT|onkeydown|thickbox|navigator|userAgent|true|span|length|Math|tb_show|match|onload|Image|toLowerCase|close|or|Esc|Key|TB_ajaxWindowTitle|TB_PrevCaption|TB_PrevURL|goNext|name|tb_init|TB_NextURL|tb_showIframe|imgLoader|TB_imageCount|TB_FoundURL|new|random|Close|urlNoQuery|split|TB_HideSelect|modal|TB_NextCaption|tb_getPageSize|innerWidth|children|jpg|jpeg|png|gif|bmp|inlineId|TB_ImageOff|getElementById|alt|self|innerHeight|typeof|rel|arrayPageSize|maxHeight|undefined|clientHeight|tb_detectMacXFF|addClass|window||for||event|keyCode|which|clientWidth|1000|parseInt|img|TB_next|replace|unescape|tb_parseQuery|auto|round|onkeyup|TB_prev|150|hspace|TB_iframe|100|frameborder|unload|TB_title|overflow|TB_closeAjaxWindow|TB_imageOff|TB_|440|40|630|188|190|TB_closeWindow|TB_secondLine|TB_caption|TB_Image|class|TB_modal|scrollTop|TB_inline|60|ready|of|Prev|lt|safari|load|Date|getTime|gt|catch|45|fadeOut||fast|trigger|Next|marginLeft|get|substr|msie|version|marginTop|continue|show|documentElement|TB_overlayBG|TB_overlayMacFFBGHack|hidden|try|blur|tb_pathToImage|input|area|mac|firefox'.split('|'),0,{}))
