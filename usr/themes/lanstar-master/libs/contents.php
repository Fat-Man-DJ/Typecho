<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
class contents{
    public static $frag = false;
    public static function parseContent($data, $widget, $last)
    {
        $text = empty($last) ? $data : $last;
        if ($widget instanceof Widget_Archive) {
            //owo
            $text = contents::parseOwo($text);
            // 友链解析
            $text = contents::parseLink($text);
            $text = contents::parseHide($text);
            // 其它
            $text = contents::blankReplace($text);
            $text = contents::biliVideo($text);
            $text = contents::video($text);
            if (!self::$frag)
                $text = contents::fancybox($text);
            $text = contents::cidToContent($text);
        }
        return $text;
    }
    /**
     *  解析 owo 表情
     */
    public static function parseOwo($content) {
        $content = preg_replace_callback('/\:\:\(\s*(呵呵|哈哈|吐舌|太开心|笑眼|花心|小乖|乖|捂嘴笑|滑稽|你懂的|不高兴|怒|汗|黑线|泪|真棒|喷|惊哭|阴险|鄙视|酷|啊|狂汗|what|疑问|酸爽|呀咩爹|委屈|惊讶|睡觉|笑尿|挖鼻|吐|犀利|小红脸|懒得理|勉强|爱心|心碎|玫瑰|礼物|彩虹|太阳|星星月亮|钱币|茶杯|蛋糕|大拇指|胜利|haha|OK|沙发|手纸|香蕉|便便|药丸|红领巾|蜡烛|音乐|灯泡|开心|钱|咦|呼|冷|生气|弱|吐血)\s*\)/is',
            array('contents', 'parsePaopaoBiaoqingCallback'), $content);
        $content = preg_replace_callback('/\:\@\(\s*(高兴|小怒|脸红|内伤|装大款|赞一个|害羞|汗|吐血倒地|深思|不高兴|无语|亲亲|口水|尴尬|中指|想一想|哭泣|便便|献花|皱眉|傻笑|狂汗|吐|喷水|看不见|鼓掌|阴暗|长草|献黄瓜|邪恶|期待|得意|吐舌|喷血|无所谓|观察|暗地观察|肿包|中枪|大囧|呲牙|抠鼻|不说话|咽气|欢呼|锁眉|蜡烛|坐等|击掌|惊喜|喜极而泣|抽烟|不出所料|愤怒|无奈|黑线|投降|看热闹|扇耳光|小眼睛|中刀)\s*\)/is',
            array('contents', 'parseAruBiaoqingCallback'), $content);
        $content = preg_replace_callback('/\:\&\(\s*(.*?)\s*\)/is',
            array('contents', 'parseQuyinBiaoqingCallback'), $content);

        return $content;
    }
    /**
     * 泡泡表情回调函数
     *
     * @return string
     */
    public static function parsePaopaoBiaoqingCallback($match)
    {
        return '<img class="emoji" src="../usr/themes/lanstar/assets/owo/biaoqing/paopao/'. str_replace('%', '', urlencode($match[1])) . '_2x.png">';
    }

    /**
     * 阿鲁表情回调函数
     *
     * @return string
     */
    public static function parseAruBiaoqingCallback($match)
    {
        return '<img class="emoji" src="../usr/themes/lanstar/assets/owo/biaoqing/aru/'. str_replace('%', '', urlencode($match[1])) . '_2x.png">';
    }

    /**
     * 蛆音娘表情回调函数
     *
     * @return string
     */
    public static function parseQuyinBiaoqingCallback($match)
    {
        return '<img class="emoji" src="../usr/themes/lanstar/assets/owo/biaoqing/quyin/'. str_replace('%', '', urlencode($match[1])) . '.png">';
    }
    /**
     * 友链解析
     */
    public static function parseLink($text) {
        $reg = '/\[links\](.*?)\[\/links\]/s';
        if (preg_match($reg, $text)) {
            self::$frag = true;
            $rp = '<div class="links-box container-fluid"><div class="row">${1}</div></div>';
            $text = preg_replace($reg, $rp, $text);
            $pattern = '/\[(.*?)\]\((.*?)\)\+\((.*)\)/';
            $replacement = '<div class="col-lg-3 col-6 col-md-4 links-container">
		    <a href="${2}" title="${1}" target="_blank" class="links-link">
			  <div class="links-item">
			    <div class="links-img"><img src="${3}"></div>
				<div class="links-title">
				  <h4>${1}</h4>
				</div>
		      </div>
			  </a>
			</div>';
            return preg_replace($pattern, $replacement, $text);
        }else{
            return $text;
        }
    }

    public static function parseHide($text)
    {
        $reg = '/\[hide\](.*?)\[\/hide\]/sm';
        if (preg_match($reg, $text)) {
            if(!Typecho_Widget::widget('Widget_Archive')->is('single')){
                $text = preg_replace($reg,'',$text);
            }
            $db = Typecho_Db::get();
            $sql = $db->select()->from('table.comments')
                ->where('cid = ?',Typecho_Widget::widget('Widget_Archive')->cid)
                ->where('mail = ?', Typecho_Widget::widget('Widget_Archive')->remember('mail',true))
                ->limit(1);
            $result = $db->fetchAll($sql);
            if(Typecho_Widget::widget('Widget_User')->hasLogin() || $result) {
                $text = preg_replace("/\[hide\](.*?)\[\/hide\]/sm",'<div class="reply2view">$1</div>',$text);
            }
            else{
                $text = preg_replace("/\[hide\](.*?)\[\/hide\]/sm",'<div class="reply2view text-center">此处内容需要评论回复后方可阅读。</div>',$text);
            }
        }
        return $text;
    }

    /**
     * 新标签打开
     * @param $content
     * @return string|string[]|null
     */
    public static function blankReplace($content){
        $reg = '#<a(.*?) href="([^"]*/)?(([^"/]*)\.[^"]*)"(.*?)>#sm';
        if (preg_match($reg, $content)) {
            $content = preg_replace($reg, '<a$1 href="$2$3"$5 target="_blank">', $content);
            return $content;
        }
        return $content;
    }
    public static function fancybox($text)
    {
        $reg = '#<img(.*?)src="(.*?)"(.*?)>#s';
        if (preg_match($reg, $text)) {
                return preg_replace($reg, '<a data-fancybox="gallery" href="$2"><img$1 src="$2"$3></a>', $text);
        }
        return $text;
    }
    public static function biliVideo($text)
    {
        $reg = '/\[bilibili bv="(.+?)" p="(.+?)"]/sm';
        if (preg_match($reg, $text)) {
            $replacement = '<iframe class="video" src="//player.bilibili.com/player.html?bvid=$1&page=$2" scrolling="no" border="0" frameborder="no" framespacing="0" allowfullscreen="true"> </iframe>';
            return preg_replace($reg, $replacement, $text);
        }
        return $text;
    }
    public static function video($text)
    {
        $reg = '/\[video src="(.+?)"]/sm';
        if (preg_match($reg, $text)) {
            $replacement = '<iframe class="video" src="$1" scrolling="no" border="0" frameborder="no" framespacing="0" allowfullscreen="true"> </iframe>';
            return preg_replace($reg, $replacement, $text);
        }
        return $text;
    }
    public static function cidToContent($text)
    {
        $reg = '/\[cid="(.+?)"]/';
        if (preg_match_all($reg, $text, $matches)) {
            $db = Typecho_Db::get();
            foreach ($matches[1] as $match) {
                $result = $db->fetchAll($db->select()->from('table.fields')
                    ->where('cid = ?',$match)
                );
                $articleArr = $db->fetchAll($db->select()->from('table.contents')
                    ->where('status = ?','publish')
                    ->where('type = ?', 'post')
                    ->where('cid = ?',$match)
                );
                $val = Typecho_Widget::widget('Widget_Abstract_Contents')->push($articleArr[0]);
                $banner = empty($result[0]['str_value'])?'../usr/themes/lanstar/assets/img/default.png':$result[0]['str_value'];

                $replacement = '<div class="card bg-dark text-white">
                              <img src="'.$banner.'" class="card-img" alt="文章卡片">
                              <div class="card-img-overlay">
                                <span class="card-title"><a href="'.$val['permalink'].'">'.$val['title'].'</a></span>
                                <p class="card-text">'.$result[1]['excerpt'].'</p>
                                <p class="card-text">'.date('Y-m-d H:i:s', $val['modified']).'</p>
                              </div>
                            </div>';
                $text =  preg_replace($reg, $replacement, $text, 1);
            }
        }
        return $text;
    }

}
