/**
 * 代码块复制按钮
 *
 * 移植自 CopyCode.astro。按钮由后端 chiri_decorate_code_blocks() 注入，
 * 本脚本负责绑定点击行为与图标状态切换。
 */
(function () {
  'use strict';

  var cfg = window.Chiri || {};

  var COPY_ICON =
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="14" height="14" fill="currentColor">' +
    '<path d="M0 6.75C0 5.784.784 5 1.75 5h1.5a.75.75 0 0 1 0 1.5h-1.5a.25.25 0 0 0-.25.25v7.5c0 .138.112.25.25.25h7.5a.25.25 0 0 0 .25-.25v-1.5a.75.75 0 0 1 1.5 0v1.5A1.75 1.75 0 0 1 9.25 16h-7.5A1.75 1.75 0 0 1 0 14.25Z"></path>' +
    '<path d="M5 1.75C5 .784 5.784 0 6.75 0h7.5C15.216 0 16 .784 16 1.75v7.5A1.75 1.75 0 0 1 14.25 11h-7.5A1.75 1.75 0 0 1 5 9.25Zm1.75-.25a.25.25 0 0 0-.25.25v7.5c0 .138.112.25.25.25h7.5a.25.25 0 0 0 .25-.25v-7.5a.25.25 0 0 0-.25-.25Z"></path>' +
    '</svg>';

  var COPIED_ICON =
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16" fill="currentColor">' +
    '<path d="M13.78 4.22a.75.75 0 0 1 0 1.06l-7.25 7.25a.75.75 0 0 1-1.06 0L2.22 9.28a.751.751 0 0 1 .018-1.042.751.751 0 0 1 1.042-.018L6 10.94l6.72-6.72a.75.75 0 0 1 1.06 0Z"></path>' +
    '</svg>';

  function setState(button, state) {
    button.dataset.copyState = state;
  }

  function showIdle(button) {
    setState(button, 'idle');
    button.innerHTML = COPY_ICON;
  }

  function showCopied(button) {
    window.clearTimeout(button._resetTimer);
    setState(button, 'copied');
    button.innerHTML = COPIED_ICON;
    button._resetTimer = window.setTimeout(function () {
      showIdle(button);
    }, 1500);
  }

  function copyText(text) {
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(text);
    }

    // 回退到 execCommand，兼容非 HTTPS 环境
    return new Promise(function (resolve, reject) {
      var ta = document.createElement('textarea');
      ta.value = text;
      ta.setAttribute('readonly', '');
      ta.style.position = 'fixed';
      ta.style.left = '-9999px';
      document.body.appendChild(ta);
      ta.select();

      try {
        var ok = document.execCommand('copy');
        document.body.removeChild(ta);
        ok ? resolve() : reject(new Error('copy failed'));
      } catch (err) {
        document.body.removeChild(ta);
        reject(err);
      }
    });
  }

  function init() {
    document.body.setAttribute('data-copy-code', cfg.copyCode ? 'enabled' : 'disabled');
    if (!cfg.copyCode) return;

    var buttons = document.querySelectorAll('.copy-button');

    Array.prototype.forEach.call(buttons, function (button) {
      if (button.dataset.copyInit) return;
      button.dataset.copyInit = '1';

      var pre = button.closest('.copy-code-block') || button.parentElement;
      if (!pre) return;

      var code = pre.querySelector('code');
      if (!code) return;

      if (!button.querySelector('svg')) {
        button.innerHTML = COPY_ICON;
      }
      setState(button, 'idle');

      button.addEventListener('click', function () {
        var text = code.textContent || '';
        copyText(text).then(
          function () {
            showCopied(button);
          },
          function (err) {
            console.error('Failed to copy code:', err);
            showIdle(button);
          }
        );
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();