<?php
// Shared print helper — include this where you need a print-only view of #reportContent
?>
<script>
function printReport() {
    var content = document.getElementById('reportContent');
    if (!content) { window.print(); return; }
    var head = document.querySelector('head').innerHTML;
    var w = window.open('', '_blank', 'width=1000,height=800');
    w.document.open();
    w.document.write('<!doctype html><html><head>' + head + '</head><body>' + content.outerHTML + '</body></html>');
    w.document.close();
    w.focus();
    setTimeout(function(){ w.print(); }, 500);
}
</script>
