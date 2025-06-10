<?php
require __DIR__ . '/../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once './class/exporter.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

$exporter = new StormwareExporter($db, $mysoc);
$invoices = [];

if (!empty($_POST['invoice_ids'])) {
    // Přednost má ruční výběr
    $ids = array_map('intval', $_POST['invoice_ids']);
    $invoices = $exporter->getInvoicesByIds($ids);
} elseif (!empty($_POST['month']) && !empty($_POST['year'])) {
    $month = (int)$_POST['month'];
    $year = (int)$_POST['year'];
    $invoices = $exporter->getInvoicesByMonth($month, $year);
} else {
    accessforbidden('Musíte zadat buď měsíc a rok, nebo vybrat faktury.');
}

$xmlString = $exporter->buildXml($invoices);
$filename = 'stormware_batch_' . date('Ym') . '.xml';
header('Content-Type: application/xml');
header('Content-Disposition: attachment; filename="'.$filename.'"');

echo $xmlString;
exit;

// When submitted
/*if ($_SERVER['REQUEST_METHOD'] === 'POST' && GETPOST('export')) {

}*/

llxFooter();
