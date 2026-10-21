<?php
/**
 * Chiri 主题函数入口：载入核心模块、注册主题设置项
 *
 * @package Chiri
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/utils.php';

/**
 * 主题内统一的翻译函数。
 *
 * ⚠️ 绝不能把 $domain 传给 _t()：Typecho 的 _t($string, ...$args) 从第二个参数起
 *    是 sprintf 的格式化参数，没有 gettext 的「域」概念。传进去会把占位符顶掉
 *    （_t('%d 条评论', 'chiri') → '0 条评论'），这里保留形参只为兼容既有调用点。
 */
function chiri_t($text, $domain = 'chiri')
{
    if (function_exists('_t')) {
        return _t($text);
    }

    return $text;
}

/**
 * 主题内统一的翻译输出函数（同 chiri_t()：不要把 $domain 传给 _e()）
 */
function chiri_e($text, $domain = 'chiri')
{
    if (function_exists('_e')) {
        _e($text);
        return;
    }

    echo $text;
}

/** 带参数的翻译（等价于 Typecho 的 _t($text, ...$args)） */
function chiri_tf($text, ...$args)
{
    if (function_exists('_t')) {
        return _t($text, ...$args);
    }

    return empty($args) ? $text : vsprintf($text, $args);
}

/** 带参数的翻译，直接输出 */
function chiri_ef($text, ...$args)
{
    echo chiri_tf($text, ...$args);
}

/** 主题设置：呈现在「控制台 → 设置外观 → 主题设置」 */
function themeConfig($form)
{
    // ---- 站点信息 ----
    $form->addInput(new Typecho_Widget_Helper_Form_Element_Text(
        'siteTitle',
        null,
        chiri_default('site.title'),
        chiri_t('站点标题', 'chiri'),
        chiri_t('显示在页面左上角', 'chiri')
    ));

    $form->addInput(new Typecho_Widget_Helper_Form_Element_Text(
        'siteAuthor',
        null,
        chiri_default('site.author'),
        chiri_t('作者名称', 'chiri'),
        chiri_t('显示在页脚版权信息中', 'chiri')
    ));

    $form->addInput(new Typecho_Widget_Helper_Form_Element_Text(
        'siteFavicon',
        null,
        chiri_default('site.favicon'),
        chiri_t('站点 Logo 路径', 'chiri'),
        chiri_t('留空则不显示，例：/usr/uploads/logo.png', 'chiri')
    ));

    $form->addInput(new Typecho_Widget_Helper_Form_Element_Text(
        'siteIcp',
        null,
        chiri_default('site.icp'),
        chiri_t('ICP 备案号', 'chiri'),
        chiri_t('显示在页脚版权行，自动链接到工信部备案系统。需开启「页脚」开关才会显示；留空则不显示。例：粤ICP备2024000000号', 'chiri')
    ));

    // ---- 外观 ----
    $form->addInput(new Typecho_Widget_Helper_Form_Element_Text(
        'contentWidth',
        null,
        chiri_default('general.contentWidth'),
        chiri_t('内容区宽度', 'chiri'),
        chiri_t('单位 rem，取值范围 25-50，默认 35rem', 'chiri')
    ));

    $form->addInput(new Typecho_Widget_Helper_Form_Element_Radio(
        'centeredLayout',
        array('true' => chiri_t('居中', 'chiri'), 'false' => chiri_t('左对齐', 'chiri')),
        chiri_default('general.centeredLayout') ? 'true' : 'false',
        chiri_t('页面布局', 'chiri')
    ));

    $form->addInput(new Typecho_Widget_Helper_Form_Element_Checkbox(
        'themeToggle',
        array('1' => chiri_t('显示明暗切换按钮', 'chiri')),
        chiri_default('general.themeToggle') ? array('1') : array(),
        chiri_t('主题切换', 'chiri'),
        chiri_t('关闭时跟随系统偏好', 'chiri')
    ));

    $form->addInput(new Typecho_Widget_Helper_Form_Element_Checkbox(
        'showSearch',
        array('1' => chiri_t('显示搜索按钮', 'chiri')),
        chiri_default('general.showSearch') ? array('1') : array(),
        chiri_t('搜索', 'chiri'),
        chiri_t('点击图标在页面中央弹出搜索框，背景自动虚化', 'chiri')
    ));

    $form->addInput(new Typecho_Widget_Helper_Form_Element_Checkbox(
        'postListDottedDivider',
        array('1' => chiri_t('列表使用点状分隔线', 'chiri')),
        chiri_default('general.postListDottedDivider') ? array('1') : array(),
        chiri_t('文章列表', 'chiri')
    ));

    $form->addInput(new Typecho_Widget_Helper_Form_Element_Checkbox(
        'footer',
        array('1' => chiri_t('显示页脚（含 ICP 备案号）', 'chiri')),
        chiri_default('general.footer') ? array('1') : array(),
        chiri_t('页脚', 'chiri'),
        chiri_t('关闭时整个页脚都不渲染，ICP 备案号会一并隐藏', 'chiri')
    ));

    $form->addInput(new Typecho_Widget_Helper_Form_Element_Checkbox(
        'showAbout',
        array('1' => chiri_t('首页显示个人简介', 'chiri')),
        chiri_default('general.showAbout') ? array('1') : array(),
        chiri_t('首页简介', 'chiri')
    ));

    $form->addInput(new Typecho_Widget_Helper_Form_Element_Textarea(
        'aboutContent',
        null,
        chiri_default('general.aboutContent'),
        chiri_t('首页简介内容', 'chiri'),
        chiri_t('支持 HTML，显示在首页文章列表上方；上方开关关闭时不显示', 'chiri')
    ));

    $form->addInput(new Typecho_Widget_Helper_Form_Element_Checkbox(
        'showReadingTime',
        array('1' => chiri_t('首页列表显示阅读时长', 'chiri')),
        chiri_default('general.showReadingTime') ? array('1') : array(),
        chiri_t('列表阅读时长', 'chiri'),
        chiri_t('窄屏下自动隐藏', 'chiri')
    ));

    // ---- 日期 ----
    $form->addInput(new Typecho_Widget_Helper_Form_Element_Select(
        'dateFormat',
        array(
            'YYYY-MM-DD'    => '2026-01-31',
            'MM-DD-YYYY'    => '01-31-2026',
            'DD-MM-YYYY'    => '31-01-2026',
            'MONTH DAY YYYY' => 'Jan 31 2026',
            'DAY MONTH YYYY' => '31 Jan 2026',
        ),
        chiri_default('date.dateFormat'),
        chiri_t('日期格式', 'chiri')
    ));

    $form->addInput(new Typecho_Widget_Helper_Form_Element_Text(
        'dateSeparator',
        null,
        chiri_default('date.dateSeparator'),
        chiri_t('数字日期分隔符', 'chiri'),
        chiri_t('仅 . - / 生效，默认 .', 'chiri')
    ));

    $form->addInput(new Typecho_Widget_Helper_Form_Element_Radio(
        'dateOnRight',
        array('true' => chiri_t('日期在右', 'chiri'), 'false' => chiri_t('日期在左', 'chiri')),
        chiri_default('date.dateOnRight') ? 'true' : 'false',
        chiri_t('列表日期位置', 'chiri')
    ));

    // ---- 文章 ----
    $form->addInput(new Typecho_Widget_Helper_Form_Element_Checkbox(
        'readingTime',
        array('1' => chiri_t('文章页显示阅读时长', 'chiri')),
        chiri_default('post.readingTime') ? array('1') : array(),
        chiri_t('阅读时长', 'chiri')
    ));

    $form->addInput(new Typecho_Widget_Helper_Form_Element_Checkbox(
        'showCategory',
        array('1' => chiri_t('文章页显示所属分类', 'chiri')),
        chiri_default('post.showCategory') ? array('1') : array(),
        chiri_t('所属分类', 'chiri'),
        chiri_t('显示于文章页日期行，隐式文本风格，点击进入分类归档', 'chiri')
    ));

    $form->addInput(new Typecho_Widget_Helper_Form_Element_Checkbox(
        'toc',
        array('1' => chiri_t('显示侧边目录', 'chiri')),
        chiri_default('post.toc') ? array('1') : array(),
        chiri_t('文章目录', 'chiri'),
        chiri_t('需页面宽度足够，自动隐藏于移动端', 'chiri')
    ));

    $form->addInput(new Typecho_Widget_Helper_Form_Element_Select(
        'tocDepth',
        array('2' => '2 级', '3' => '3 级', '4' => '4 级'),
        (string) chiri_default('post.tocDepth', 3),
        chiri_t('目录层级', 'chiri')
    ));

    $form->addInput(new Typecho_Widget_Helper_Form_Element_Checkbox(
        'copyCode',
        array('1' => chiri_t('代码块显示复制按钮', 'chiri')),
        chiri_default('post.copyCode') ? array('1') : array(),
        chiri_t('代码复制', 'chiri')
    ));

    $form->addInput(new Typecho_Widget_Helper_Form_Element_Checkbox(
        'imageViewer',
        array('1' => chiri_t('图片查看器', 'chiri')),
        chiri_default('post.imageViewer') ? array('1') : array(),
        chiri_t('点击正文图片可放大', 'chiri')
    ));

    $form->addInput(new Typecho_Widget_Helper_Form_Element_Checkbox(
        'katex',
        array('1' => chiri_t('启用数学公式渲染', 'chiri')),
        chiri_default('post.katex') ? array('1') : array(),
        chiri_t('KaTeX', 'chiri')
    ));

    $form->addInput(new Typecho_Widget_Helper_Form_Element_Checkbox(
        'comments',
        array('1' => chiri_t('显示评论区', 'chiri')),
        chiri_default('post.comments') ? array('1') : array(),
        chiri_t('评论区', 'chiri'),
        chiri_t('关闭后文章页与独立页都不再显示评论列表与评论表单；单篇文章自身的「允许评论」仍需在「管理 → 文章」中单独设置', 'chiri')
    ));
}
