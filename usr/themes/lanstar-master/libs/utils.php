<?php
class utils
{
    /**
     * 首页幻灯片的处理
     * @param $content
     * @return string
     */
    public static function bannerHandle($content)
    {
        $bannerArr = explode(PHP_EOL, $content);
        $text = '';
        if (count($bannerArr) > 3) {
            //打乱数组
            shuffle($bannerArr);
            $bannerArr = array_slice($bannerArr,0,3);
        }
        foreach ($bannerArr as $key => $banner) {
            if ($key) {
                $text .= '<div class="carousel-item">
                    <img src="'.$banner.'" class="d-block w-100" alt="banner">
                </div>';
            } else {
                $text .= '<div class="carousel-item active">
                    <img src="'.$banner.'" class="d-block w-100" alt="banner">
                </div>';
            }
        }
        return $text;
    }

    /**
     * 判断插件是否可用（存在且已激活）
     * @param $name
     * @return bool
     */
    public static function hasPlugin($name)
    {
        $plugins = Typecho_Plugin::export();
        $plugins = $plugins['activated'];
        return is_array($plugins) && array_key_exists($name, $plugins);
    }

    /**
     * 给导航添加图标
     * @param $nav
     * @param $text
     */
    public static function customNavHandle($nav, $pages, $that)
    {
        $navArr = explode(PHP_EOL, $nav);
        $content = '';
        $count = count($navArr);
        $start = count($navArr);
        while ($pages->next()) {
            if ($that->is('page', $pages->slug)):
                $class="nav-link active";
            else:
                $class="nav-link";
            endif;
            if ($count) {
                $url = Helper::options()->themeUrl .'/assets/img/bootstrap-icons.svg' . '#'. $navArr[$start-$count];
                $content .= '
                <a class="'.$class.'" href="'.$pages->permalink.'" title="'.$pages->title.'">
                        <div class="nav-item">
                        <svg class="nav-icon bi" width="24" height="24" fill="currentColor">
                            <use xlink:href="'.$url.'"/>
                        </svg>
                        <span class="nav-item-text">'.$pages->title.'</span>
                    </div>
                </a>';
                $count--;
            } else {
                $url = Helper::options()->themeUrl.'/assets/img/bootstrap-icons.svg#cursor';
                $content .= '
                    <a class="'.$class.'" href="'.$pages->permalink.'" title="'.$pages->title.'">
                            <div class="nav-item">
                            <svg class="nav-icon bi" width="24" height="24" fill="currentColor">
                                <use xlink:href="'.$url.'"/>
                            </svg>
                            <span class="nav-item-text">'.$pages->title.'</span>
                        </div>
                    </a>';
            }
        }
        echo $content;
    }
    /**
     * 文章中文字数统计
     * @param $cid
     */
    public static function artCount ($cid){
        $db=Typecho_Db::get ();
        $rs=$db->fetchRow ($db->select ('table.contents.text')->from ('table.contents')->where ('table.contents.cid=?',$cid)->order ('table.contents.cid',Typecho_Db::SORT_ASC)->limit (1));
        $text = preg_replace("/[^\x{4e00}-\x{9fa5}]/u", "", $rs['text']);
        echo mb_strlen($text,'UTF-8');
    }

    /**
     * 文章阅读次数统计
     * @param $archive
     */
    public static function getPostView($archive)
    {
        $cid    = $archive->cid;
        $db     = Typecho_Db::get();
        $prefix = $db->getPrefix();
        if (!array_key_exists('views', $db->fetchRow($db->select()->from('table.contents')))) {
            $db->query('ALTER TABLE `' . $prefix . 'contents` ADD `views` INT(10) DEFAULT 0;');
            echo 0;
            return;
        }
        $row = $db->fetchRow($db->select('views')->from('table.contents')->where('cid = ?', $cid));
        if ($archive->is('single')) {
            $views = Typecho_Cookie::get('extend_contents_views');
            if(empty($views)){
                $views = array();
            }else{
                $views = explode(',', $views);
            }
            if(!in_array($cid,$views)){
                $db->query($db->update('table.contents')->rows(array('views' => (int) $row['views'] + 1))->where('cid = ?', $cid));
                array_push($views, $cid);
                $views = implode(',', $views);
                Typecho_Cookie::set('extend_contents_views', $views); //记录查看cookie
            }
        }
        echo $row['views'];
    }
    /**
     * 编辑界面添加Button
     *
     * @return void
     */
    public static function addButton()
    {
        echo '<script src="';
        Helper::options()->themeUrl('/assets/js/all.min.js');
        echo '"></script>';
        echo '<script src="';
        Helper::options()->themeUrl('/assets/owo/owo_02.js');
        echo '"></script>';

        echo '<script src="';
        Helper::options()->themeUrl('/assets/js/editor.js');
        echo '"></script>';

        echo '<link rel="stylesheet" href="';
        Helper::options()->themeUrl('/assets/owo/owo.min.css');
        echo '" />';

        echo '<style>#custom-field textarea,#custom-field input{width:100%}
        .OwO span{background:none!important;width:unset!important;height:unset!important}
        .OwO .OwO-body .OwO-items{
            -webkit-overflow-scrolling: touch;
            overflow-x: hidden;
        }
        .OwO .OwO-body .OwO-items-image .OwO-item{
            max-width:-moz-calc(20% - 10px);
            max-width:-webkit-calc(20% - 10px);
            max-width:calc(20% - 10px)
        }
        @media screen and (max-width:767px){	
            .comment-info-input{flex-direction:column;}
            .comment-info-input input{max-width:100%;margin-top:5px}
            #comments .comment-author .avatar{
                width: 2.5rem;
                height: 2.5rem;
            }
        }
        @media screen and (max-width:760px){
            .OwO .OwO-body .OwO-items-image .OwO-item{
                max-width:-moz-calc(25% - 10px);
                max-width:-webkit-calc(25% - 10px);
                max-width:calc(25% - 10px)
            }
        }
        .wmd-button-row{height:unset}</style>';
    }
    public static function agreeNum($cid) {
        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();

        //  判断点赞数量字段是否存在
        if (!array_key_exists('agree', $db->fetchRow($db->select()->from('table.contents')))) {
            //  在文章表中创建一个字段用来存储点赞数量
            $db->query('ALTER TABLE `' . $prefix . 'contents` ADD `agree` INT(10) NOT NULL DEFAULT 0;');
        }

        //  查询出点赞数量
        $agree = $db->fetchRow($db->select('table.contents.agree')->from('table.contents')->where('cid = ?', $cid));
        //  获取记录点赞的 Cookie
        $AgreeRecording = Typecho_Cookie::get('typechoAgreeRecording');
        //  判断记录点赞的 Cookie 是否存在
        if (empty($AgreeRecording)) {
            //  如果不存在就写入 Cookie
            Typecho_Cookie::set('typechoAgreeRecording', json_encode(array(0)));
        }

        //  返回
        return array(
            //  点赞数量
            'agree' => $agree['agree'],
            //  文章是否点赞过
            'recording' => in_array($cid, json_decode(Typecho_Cookie::get('typechoAgreeRecording')))?true:false
        );
    }
    public static function agree($cid) {
        $db = Typecho_Db::get();
        //  根据文章的 `cid` 查询出点赞数量
        $agree = $db->fetchRow($db->select('table.contents.agree')->from('table.contents')->where('cid = ?', $cid));

        //  获取点赞记录的 Cookie
        $agreeRecording = Typecho_Cookie::get('typechoAgreeRecording');
        //  判断 Cookie 是否存在
        if (empty($agreeRecording)) {
            //  如果 cookie 不存在就创建 cookie
            Typecho_Cookie::set('typechoAgreeRecording', json_encode(array($cid)));
        }else {
            //  把 Cookie 的 JSON 字符串转换为 PHP 对象
            $agreeRecording = json_decode($agreeRecording);
            //  判断文章是否点赞过
            if (in_array($cid, $agreeRecording)) {
                //  如果当前文章的 cid 在 cookie 中就返回文章的赞数，不再往下执行
                return $agree['agree'];
            }
            //  添加点赞文章的 cid
            array_push($agreeRecording, $cid);
            //  保存 Cookie
            Typecho_Cookie::set('typechoAgreeRecording', json_encode($agreeRecording));
        }

        //  更新点赞字段，让点赞字段 +1
        $db->query($db->update('table.contents')->rows(array('agree' => (int)$agree['agree'] + 1))->where('cid = ?', $cid));
        //  查询出点赞数量
        $agree = $db->fetchRow($db->select('table.contents.agree')->from('table.contents')->where('cid = ?', $cid));
        //  返回点赞数量
        return $agree['agree'];
    }

}
