document.querySelectorAll('[data-confirm]').forEach(function (el) {
  el.addEventListener('click', function (e) { if (!confirm(el.dataset.confirm)) e.preventDefault(); });
  el.addEventListener('submit', function (e) { if (!confirm(el.dataset.confirm)) e.preventDefault(); });
});
document.querySelectorAll('[data-bb]').forEach(function (b) {
  b.addEventListener('click', function () {
    var t = document.querySelector('textarea[name=body]'), s = t.selectionStart, e = t.selectionEnd, tag = b.dataset.bb;
    t.setRangeText('[' + tag + ']' + t.value.slice(s, e) + '[/' + tag + ']', s, e, 'end'); t.focus();
  });
});
