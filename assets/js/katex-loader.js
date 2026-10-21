/**
 * KaTeX 按需加载
 *
 * Astro 源主题在构建期通过 rehype-katex 直接输出 HTML；
 * Typecho 环境无法预渲染，因此改为检测到公式时再异步加载 KaTeX。
 *
 * 资源走 CDN，避免主题包内附带 20 个字体文件。
 */
(function () {
  'use strict';

  var CDN = 'https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/';

  function hasMath(root) {
    if (!root) return false;

    // 常见数学标记：\( \)、\[ \]、$$、行内 $...$ 与 \begin{...}
    if (root.querySelector('.katex, .katex-display')) return true;

    var text = root.textContent || '';
    if (/\$\$[\s\S]+?\$\$/.test(text)) return true;
    if (/\\\([\s\S]+?\\\)/.test(text)) return true;
    if (/\\\[[\s\S]+?\\\]/.test(text)) return true;
    if (/\\begin\{(equation|align|matrix|pmatrix|bmatrix|cases|array)\}/.test(text)) return true;

    return false;
  }

  var CONTENT = '.prose .content, .prose';

  function render() {
    if (!window.katex || !CONTENT) return;

    var nodes = document.querySelectorAll(CONTENT);
    if (!nodes.length) return;

    var el = nodes[0];
    var text = el.textContent || '';

    function renderIn(selector, displayMode) {
      var items = document.querySelectorAll(selector);
      Array.prototype.forEach.call(items, function (node) {
        try {
          window.katex.render(node.textContent, node, {
            displayMode: displayMode,
            throwOnError: false,
            output: 'html'
          });
        } catch (e) {
          /* 解析失败时保留原始文本 */
        }
      });
    }

    // 先处理块级 $$...$$
    Array.prototype.forEach.call(el.querySelectorAll('p'), function (p) {
      var html = p.innerHTML.trim();
      if (/^\$\$[\s\S]+\$\$$/.test(html)) {
        try {
          window.katex.render(p.textContent.replace(/^\$\$|\$\$$/g, ''), p, {
            displayMode: true,
            throwOnError: false
          });
        } catch (e) {
          /* ignore */
        }
      }
    });

    renderIn('.math.inline', false);
    renderIn('.math.display', true);
  }

  function load() {
    if (window.katex) {
      render();
      return;
    }

    var css = document.createElement('link');
    css.rel = 'stylesheet';
    css.href = CDN + 'katex.min.css';
    document.head.appendChild(css);

    var js = document.createElement('script');
    js.defer = true;
    js.src = CDN + 'katex.min.js';
    js.onload = render;
    document.head.appendChild(js);
  }

  function init() {
    var root = document.querySelector(CONTENT);
    if (!hasMath(root)) return;
    load();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();