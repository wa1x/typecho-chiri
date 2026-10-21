<?php
/**
 * 评论列表与表单（对照 Typecho 默认主题实现）
 *
 * 列表结构由内核的 listComments() 输出；回复锚点用 respondId() + cancelReply()；
 * 网址必填项的选项名是 commentsRequireUrl（不是 commentsRequireURL）。
 *
 * @package Chiri
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

$this->comments()->to($comments);
?>
<div id="comments" class="comments-area">
  <?php if ($comments->have()): ?>
  <h3 class="comments-title"><?php $this->commentsNum(
      chiri_t('暂无评论', 'chiri'),
      chiri_t('1 条评论', 'chiri'),
      chiri_t('%d 条评论', 'chiri')
  ); ?></h3>

  <?php $comments->listComments(array('replyWord' => chiri_t('回复', 'chiri'))); ?>

  <?php $comments->pageNav(chiri_t('上一页', 'chiri'), chiri_t('下一页', 'chiri')); ?>
  <?php endif; ?>

  <?php if ($this->allow('comment')): ?>
  <div id="<?php $this->respondId(); ?>" class="comments-respond">
    <div class="cancel-comment-reply">
      <?php $comments->cancelReply(chiri_t('取消回复', 'chiri')); ?>
    </div>

    <h3 class="comments-title"><?php chiri_e('添加新评论', 'chiri'); ?></h3>

    <form method="post" action="<?php $this->commentUrl(); ?>" id="comment_form" role="form">
      <?php if ($this->user->hasLogin()): ?>
      <p class="comment-logged">
        <?php chiri_e('登录身份', 'chiri'); ?>:
        <a href="<?php $this->options->profileUrl(); ?>"><?php $this->user->screenName(); ?></a>.
        <a href="<?php $this->options->logoutUrl(); ?>" title="Logout"><?php chiri_e('退出', 'chiri'); ?> &raquo;</a>
      </p>
      <?php else: ?>
      <p class="comment-field">
        <label for="author" class="required"><?php chiri_e('称呼', 'chiri'); ?></label>
        <input type="text" name="author" id="author" class="text" size="35"
               value="<?php $this->remember('author'); ?>"
               placeholder="<?php chiri_e('称呼 *', 'chiri'); ?>" required />
      </p>
      <p class="comment-field">
        <label for="mail"<?php if ($this->options->commentsRequireMail): ?> class="required"<?php endif; ?>><?php chiri_e('邮箱', 'chiri'); ?></label>
        <input type="email" name="mail" id="mail" class="text" size="35"
               value="<?php $this->remember('mail'); ?>"
               placeholder="<?php chiri_e('邮箱（不会公开）', 'chiri'); ?>"<?php if ($this->options->commentsRequireMail): ?> required<?php endif; ?> />
      </p>
      <p class="comment-field">
        <label for="url"<?php if ($this->options->commentsRequireUrl): ?> class="required"<?php endif; ?>><?php chiri_e('网站', 'chiri'); ?></label>
        <input type="url" name="url" id="url" class="text" size="35" placeholder="https://"
               value="<?php $this->remember('url'); ?>"<?php if ($this->options->commentsRequireUrl): ?> required<?php endif; ?> />
      </p>
      <?php endif; ?>

      <p class="comment-field">
        <label for="textarea" class="required"><?php chiri_e('内容', 'chiri'); ?></label>
        <textarea rows="8" cols="50" name="text" id="textarea" class="textarea"
                  placeholder="<?php chiri_e('写下你的评论…', 'chiri'); ?>" required><?php $this->remember('text'); ?></textarea>
      </p>

      <p class="comment-submit">
        <button type="submit" class="submit"><?php chiri_e('提交评论', 'chiri'); ?></button>
      </p>
    </form>
  </div>
  <?php else: ?>
  <p class="comment-closed"><?php chiri_e('评论已关闭', 'chiri'); ?></p>
  <?php endif; ?>
</div>
