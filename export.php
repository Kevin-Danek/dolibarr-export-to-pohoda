<?php
require __DIR__ . '/../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

llxHeader('', 'Export Faktur');
print load_fiche_titre("Export to Pohoda");

echo '<form method="POST" action="serialize.php">';
echo '<input type="hidden" name="token" value="'.newToken().'">';
echo '<fieldset><legend>Filtr podle měsíce</legend>';
echo 'Měsíc: <select name="month">';
for ($m = 1; $m <= 12; $m++) {
    echo '<option value="'.$m.'">'.$m.'</option>';
}
echo '</select> ';
echo 'Rok: <input type="number" name="year" value="'.date('Y').'" min="2000" max="2100">';
echo '</fieldset>';

echo '<div class="center">';
echo '<input class="button" type="submit" name="export" value="Exportovat">';
echo '</div>';
echo '</form>';

llxFooter();
