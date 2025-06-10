function pdf_build_substitutionarray($parameters, &$object, &$hookmanager)
{
    global $conf;

    // Generuj název souboru pro QR kód – unikátní pro daný dokument
    $fileName = 'qrcode_' . $object->ref . '.png';

    // Cesta k dočasnému souboru
    $filePath = $conf->dolibarr_main_data_root . '/temp/' . $fileName;

    // Sestav odkaz pro QR kód z dat z faktury
    $url = 'https://api.paylibo.com/paylibo/generator/czech/image?accountNumber=' . $object->thirdparty->code_client;
    $url .= '&bankCode=5500&amount=' . price2num($object->total_ttc);
    $url .= '&currency=CZK&vs=' . $object->ref;
    $url .= '&message=' . urlencode('Faktura č. ' . $object->ref);

    // Ulož QR kód z URL
    file_put_contents($filePath, file_get_contents($url));

    // Nastav do substitučního pole
    if (file_exists($filePath)) {
        $parameters['substitutionarray']['qrcode_logo'] = $filePath;
    }

    return 1;
}
