<?php
/**
 * Chiri 主题工具函数：日期格式化、阅读时长、正文后处理（图片 / 代码块 / 目录）
 *
 * @package Chiri
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/** 按主题配置格式化日期（5 种格式 × 3 种分隔符） */
function chiri_format_date($timestamp = null)
{
    $format    = trim((string) chiri_config('date.dateFormat', 'YYYY-MM-DD'));
    $separator = trim((string) chiri_config('date.dateSeparator', '.'));

    if (!in_array($separator, array('.', '-', '/'), true)) {
        $separator = '.';
    }

    if ($timestamp === null || $timestamp === '') {
        return '';
    }

    // Typecho 1.2+ 的 $this->date 是 Typecho\Date 对象，直接 strtotime() 会抛 TypeError
    if (is_object($timestamp)) {
        if (isset($timestamp->timeStamp)) {
            $time = (int) $timestamp->timeStamp;
        } elseif (method_exists($timestamp, 'format')) {
            $time = (int) $timestamp->format('U');
        } else {
            return '';
        }
    } elseif (is_numeric($timestamp)) {
        $time = (int) $timestamp;
    } else {
        $time = strtotime((string) $timestamp);
    }

    if ($time === false || $time <= 0) {
        return '';
    }

    $months = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');

    $year  = (int) date('Y', $time);
    $month = (int) date('n', $time);
    $day   = (int) date('j', $time);
    $name  = $months[(int) date('n', $time) - 1];

    switch ($format) {
        case 'MM-DD-YYYY':
            return sprintf('%02d%s%02d%s%d', $month, $separator, $day, $separator, $year);
        case 'DD-MM-YYYY':
            return sprintf('%02d%s%02d%s%d', $day, $separator, $month, $separator, $year);
        case 'MONTH DAY YYYY':
            return sprintf('<span class="month">%s</span> %d %d', $name, $day, $year);
        case 'DAY MONTH YYYY':
            return sprintf('%d <span class="month">%s</span> %d', $day, $name, $year);
        case 'YYYY-MM-DD':
        default:
            return sprintf('%d%s%02d%s%02d', $year, $separator, $month, $separator, $day);
    }
}

/** 输出带 <time> 语义标签的格式化日期 */
function chiri_date_html($timestamp = null)
{
    $html = chiri_format_date($timestamp);
    if ($html === '') {
        return '';
    }

    // 与 chiri_format_date 相同的归一化逻辑
    if (is_object($timestamp) && isset($timestamp->timeStamp)) {
        $time = (int) $timestamp->timeStamp;
    } elseif (is_numeric($timestamp)) {
        $time = (int) $timestamp;
    } else {
        $time = strtotime((string) $timestamp);
    }

    if ($time === false || $time <= 0) {
        return $html;
    }

    $iso = date('c', $time);

    return '<time datetime="' . htmlspecialchars($iso, ENT_QUOTES, 'UTF-8') . '">' . $html . '</time>';
}

/**
 * 包装正文图片：img → figure.image-caption-wrapper，alt 作为 figcaption
 * （与原版 ImageOptimizer 输出一致，供 post.css 与图片查看器识别）
 *
 * 属性解析采用引号感知的 tokenizer，任意属性顺序均可正确处理；
 * 若标签属性已畸形（引号未闭合等），整体跳过装饰，避免二次破坏。
 */
function chiri_decorate_images($html)
{
    if (!is_string($html) || $html === '' || strpos($html, '<img') === false) {
        return $html;
    }

    return preg_replace_callback('/<img\b([^>]*?)(?:\/>|>)/i', function ($m) {
        $attrs = chiri_parse_img_attrs($m[1]);

        // 畸形标签（如内核 bug 产生的引号破坏输出）不处理，保留原样
        if ($attrs === false) {
            return $m[0];
        }

        // data-chiri-wrapped：已在 figure 内的图片不重复包装
        if (array_key_exists('data-chiri-wrapped', $attrs)) {
            return $m[0];
        }

        $alt = isset($attrs['alt']) ? (string) $attrs['alt'] : '';

        // 追加缺失的 class / data-preview / data-chiri-wrapped
        if (!isset($attrs['class'])) {
            $attrs['class'] = 'img-placeholder';
        } elseif (strpos($attrs['class'], 'img-placeholder') === false) {
            $attrs['class'] = 'img-placeholder ' . $attrs['class'];
        }
        if (!isset($attrs['data-preview'])) {
            $attrs['data-preview'] = 'true';
        }

        // 原生懒加载 + 异步解码（post.css/components.css 的 img[loading='lazy'] 淡入依赖此属性）
        if (!isset($attrs['loading'])) {
            $attrs['loading'] = 'lazy';
        }
        if (!isset($attrs['decoding'])) {
            $attrs['decoding'] = 'async';
        }
        $attrs['data-chiri-wrapped'] = null;

        $tag = chiri_build_img_tag($attrs);

        $caption = ($alt !== '')
            ? '<figcaption class="img-caption">' . htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') . '</figcaption>'
            : '';

        return '<figure class="image-caption-wrapper">' . $tag . $caption . '</figure>';
    }, $html);
}

/**
 * 解析 img 标签内的属性串为 name => value 映射（键小写，保持出现顺序，先出现的属性优先）
 *
 * @return array|false 解析失败（引号未闭合或非法结构）时返回 false
 */
function chiri_parse_img_attrs($attrs)
{
    if (!is_string($attrs)) {
        return false;
    }

    $result = array();
    $len    = strlen($attrs);
    $i      = 0;

    while ($i < $len) {
        $ch = $attrs[$i];

        // 跳过空白与自闭合斜杠
        if (ctype_space($ch) || $ch === '/') {
            $i++;
            continue;
        }

        // 属性名
        if (!preg_match('/[a-zA-Z_:@][a-zA-Z0-9_:.]*/A', $attrs, $mm, 0, $i)) {
            return false;
        }

        $name = strtolower($mm[0]);
        $i += strlen($mm[0]);

        // 布尔属性（无 =）
        while ($i < $len && ctype_space($attrs[$i])) {
            $i++;
        }
        if ($i >= $len || $attrs[$i] !== '=') {
            if (!array_key_exists($name, $result)) {
                $result[$name] = null;
            }
            continue;
        }
        $i++;

        // 属性值
        while ($i < $len && ctype_space($attrs[$i])) {
            $i++;
        }
        if ($i >= $len) {
            return false;
        }

        $quote = $attrs[$i];
        if ($quote === '"' || $quote === "'") {
            $end = strpos($attrs, $quote, $i + 1);
            if ($end === false) {
                return false;
            }
            $value = substr($attrs, $i + 1, $end - $i - 1);
            $i     = $end + 1;
        } else {
            $start = $i;
            while ($i < $len && !ctype_space($attrs[$i])) {
                $i++;
            }
            $value = substr($attrs, $start, $i - $start);
        }

        if (!array_key_exists($name, $result)) {
            // 实体统一在解析时解码（循环至稳定，消解上游多层转义），输出时由 chiri_build_img_tag() 重新转义一次
            for ($n = 0; $n < 5; $n++) {
                $decoded = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
                if ($decoded === $value) {
                    break;
                }
                $value = $decoded;
            }
            $result[$name] = $value;
        }
    }

    return $result;
}

/** 由 name => value 映射重建 img 标签（值统一双引号 + 实体转义） */
function chiri_build_img_tag(array $attrs)
{
    $parts = array('<img');

    foreach ($attrs as $name => $value) {
        if ($value === null) {
            $parts[] = ' ' . $name;
            continue;
        }
        $parts[] = ' ' . $name . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
    }

    return implode('', $parts) . '>';
}

/** 估算阅读时长：中文按字符数、西文按词数混合统计 */
function chiri_reading_time($html)
{
    $text = trim(html_entity_decode(chiri_strip_tags($html), ENT_QUOTES, 'UTF-8'));

    if ($text === '') {
        return array('text' => '1 min read', 'minutes' => 1, 'words' => 0);
    }

    $cjkPattern = '/[\x{4e00}-\x{9fa5}\x{3040}-\x{30ff}\x{ac00}-\x{d7af}]/u';

    // UTF-8 模式不可用时回退到全角区间
    $cjk = preg_match_all($cjkPattern, $text, $m);
    if ($cjk === false) {
        $cjk = preg_match_all('/[\x80-\xff]/', $text, $m);
    }

    $latin = preg_replace($cjkPattern, ' ', $text);
    if ($latin === null) {
        $latin = $text;
    }

    $words = preg_match_all('/[A-Za-z0-9]+(?:[\'-][A-Za-z0-9]+)*/', $latin, $m2);

    $cjkCount   = (int) $cjk;
    $wordCount  = (int) $words;
    $totalUnits = (int) round($cjkCount / 2 + $wordCount);

    $minutes = (int) max(1, (int) ceil($totalUnits / 200));

    return array(
        'text'    => $minutes . ' min read',
        'minutes' => $minutes,
        'words'   => $totalUnits,
    );
}

/** 去除 HTML 标签（含 script / style 内容） */
function chiri_strip_tags($html)
{
    $html = (string) $html;

    $html = preg_replace('#<script\b[^>]*>.*?</script\s*>#is', '', $html);
    $html = preg_replace('#<style\b[^>]*>.*?</style\s*>#is', '', $html);

    return trim(strip_tags($html));
}

/**
 * 从正文提取标题生成目录（level / id / text），并回填标题 id 保证锚点可用
 *
 * @return array{list:array, html:string}
 */
function chiri_extract_toc($html, $depth = 3)
{
    $depth = max(1, min(4, (int) $depth));

    // 已是数组时直接使用（例如插件预先生成的目录）
    if (is_array($html)) {
        return array('list' => $html, 'html' => '');
    }

    $usedIds = array();
    $toc     = array();

    $pattern = '/<h([1-' . $depth . '])\b([^>]*)>(.*?)<\/h\1>/is';

    $replaced = preg_replace_callback(
        $pattern,
        function ($matches) use (&$usedIds, &$toc) {
            $level = (int) $matches[1];
            $attrs = $matches[2];
            $inner = $matches[3];

            $id = '';
            if (preg_match('/\bid\s*=\s*["\']([^"\']+)["\']/i', $attrs, $idMatch)) {
                $id = $idMatch[1];
            }

            $text = trim(html_entity_decode(chiri_strip_tags($inner), ENT_QUOTES, 'UTF-8'));

            if ($id === '') {
                $base = chiri_slugify($text);
                if ($base === '') {
                    $base = 'section';
                }
                $id      = $base;
                $suffix  = 1;
                while (isset($usedIds[$id])) {
                    $id = $base . '-' . (++$suffix);
                }
            }

            $usedIds[$id] = true;

            if ($text !== '') {
                $toc[] = array(
                    'level' => $level,
                    'id'    => $id,
                    'text'  => $text,
                );
            }

            // 先去掉原 id 再回填，保证唯一
            $cleanAttrs = preg_replace('/\sid\s*=\s*["\'][^"\']*["\']/i', '', $attrs);
            $cleanAttrs = trim((string) $cleanAttrs);

            return '<h' . $level . ($cleanAttrs !== '' ? ' ' . $cleanAttrs : '') . ' id="'
                . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '">' . $inner . '</h' . $level . '>';
        },
        $html
    );

    return array(
        'list' => $toc,
        'html' => $replaced === null ? $html : $replaced,
    );
}

/** 生成 URL 友好的锚点 ID（中文等非 ASCII 字符被剔除） */
function chiri_slugify($text)
{
    $text = trim((string) $text);
    if ($text === '') {
        return '';
    }

    $text = chiri_strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

    $text = preg_replace('/[\x{4e00}-\x{9fa5}\x{3040}-\x{30ff}\x{ac00}-\x{d7af}]+/u', '', $text);

    // \p{L} 需 PCRE UTF-8 支持，不可用时 preg_replace 返回 null，降级走下方分支
    $replaced = preg_replace('/[^\p{L}\p{N}]+/u', '-', $text);
    if ($replaced === null) {
        $replaced = preg_replace('/[^A-Za-z0-9]+/', '-', $text);
    }
    $text = $replaced === null ? '' : $replaced;

    $text = trim((string) $text, '-');

    if (function_exists('mb_strtolower')) {
        $text = mb_strtolower($text, 'UTF-8');
    } else {
        $text = strtolower($text);
    }

    $text = preg_replace('/-{2,}/', '-', $text);

    return trim((string) $text, '-');
}

/**
 * 为正文代码块包裹复制按钮容器（移植 rehype-copy-code.mjs），
 * 按钮行为由 assets/js/copy-code.js 绑定
 */
function chiri_decorate_code_blocks($html)
{
    return preg_replace_callback(
        '/<pre\b([^>]*)>(.*?)<\/pre>/is',
        function ($matches) {
            $attrs = $matches[1];
            $inner = $matches[2];

            if (strpos($attrs, 'copy-code-block') !== false) {
                return $matches[0];
            }

            // 补齐 Shiki 之外的裸 <pre>（Shiki 输出里本来就有 <code>）
            if (stripos($inner, '<code') === false) {
                $inner = '<code>' . $inner . '</code>';
            }

            $button = '<button class="copy-button" type="button" aria-label="Copy code"></button>';

            return '<div class="copy-code-wrapper"><pre class="copy-code-block"' . $attrs . '>'
                . $button . $inner . '</pre></div>';
        },
        $html
    );
}

/** 生成页面标题（archiveTitle() 输出 + 站点名后缀，异常时回落为站点名） */
function chiri_page_title($widget, $siteName)
{
    $siteName = (string) $siteName;
    $sep      = ' · ';

    $archiveTitle = '';
    if (is_object($widget) && method_exists($widget, 'archiveTitle')) {
        try {
            // Typecho 的 archiveTitle() 直接 echo，用输出缓冲捕获
            ob_start();
            $widget->archiveTitle(array(
                'category' => chiri_t('分类 %s 下的文章', 'chiri'),
                'search'   => chiri_t('包含关键字 %s 的文章', 'chiri'),
                'tag'      => chiri_t('标签 %s 下的文章', 'chiri'),
                'author'   => chiri_t('%s 发布的文章', 'chiri'),
            ), '', '');
            $archiveTitle = trim((string) ob_get_clean());
        } catch (Exception $e) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            $archiveTitle = '';
        } catch (Error $e) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            $archiveTitle = '';
        }
    }

    $archiveTitle = trim($archiveTitle);

    if ($archiveTitle === '') {
        return htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8');
    }

    return htmlspecialchars($archiveTitle, ENT_QUOTES, 'UTF-8') . $sep
        . htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8');
}

/**
 * 构造搜索结果页 URL
 *
 * 内核的搜索流程是「POST s=xxx → 302 → /search/xxx/」，但重定向依赖
 * 响应里的 Location 头：部分服务器/CDN 会丢弃它，浏览器就停在空白页。
 * 这里直接按路由表拼出结果页地址，前端跳过去，绕开这层重定向。
 *
 * 传入 '__keywords__' 可拿到带占位符的地址模板（该占位符经 urlencode 不变），
 * 前端把占位符替换成实际关键词。
 *
 * 路由格式取自内核的 'search' 路由（/search/[keywords]/），
 * 关键词做 urlencode 编码（与内核 Archive.php 里的处理一致）。
 */
function chiri_search_url($keyword)
{
    $keyword = trim((string) $keyword);

    if ($keyword === '') {
        return '';
    }

    $index = '';

    if (class_exists('Helper')) {
        try {
            $options = Helper::options();
            if (isset($options->index)) {
                $index = (string) $options->index;
            }
        } catch (Exception $e) {
            $index = '';
        } catch (Error $e) {
            $index = '';
        }
    }

    if ($index === '') {
        return '';
    }

    $encoded = urlencode($keyword);

    // 优先用内核路由生成，保证与站点自身路由配置一致
    $path = '/search/' . $encoded . '/';

    try {
        if (class_exists('Typecho\\Router')) {
            $path = Typecho\Router::url('search', ['keywords' => $encoded], $index);
        } elseif (class_exists('Typecho_Router')) {
            $path = Typecho_Router::url('search', ['keywords' => $encoded], $index);
        } else {
            $path = rtrim($index, '/') . $path;
        }
    } catch (Exception $e) {
        $path = rtrim($index, '/') . $path;
    } catch (Error $e) {
        $path = rtrim($index, '/') . $path;
    }

    return $path;
}

/** 获取当前请求的完整 URL */
function chiri_current_url()
{
    $siteUrl = '';

    if (class_exists('Helper')) {
        try {
            $options = Helper::options();
            if (isset($options->siteUrl)) {
                $siteUrl = (string) $options->siteUrl;
            }
        } catch (Exception $e) {
            $siteUrl = '';
        } catch (Error $e) {
            $siteUrl = '';
        }
    }

    $path = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';

    if ($siteUrl !== '') {
        return rtrim($siteUrl, '/') . '/' . ltrim($path, '/');
    }

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $host  = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

    return ($https ? 'https://' : 'http://') . $host . $path;
}