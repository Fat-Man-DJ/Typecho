<?php
/**
 * 友情链接
 *
 * @package custom
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$this->need('includes/header.php');
?>
<div class="container">
    <div class="row">
        <?php $this->need('includes/nav.php');?>
        <div class="col-xl-7 col-md-6 col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php $this->options->siteUrl();?>">首页</a></li>
                    <?php if ($this->is('post')): ?>
                        <li class="breadcrumb-item active" aria-current="page"><?php $this->title();?></li>
                    <?php else: ?>
                        <li class="breadcrumb-item active" aria-current="page"><?php $this->archiveTitle('&raquo;','',''); ?></li>
                    <?php endif; ?>
                </ol>
            </nav>
            <article class="post">
                <h1 class="article-title"><a href="<?php $this->permalink(); ?>"><?php $this->title(); ?></a></h1>
                <?php $this->content(); ?>
            </article>
            <?php $this->need('includes/comments.php'); ?>

        </div>
        <?php $this->need('includes/right.php');?>
    </div>
</div>