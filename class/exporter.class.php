<?php

require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

class StormwareExporter
{
    private $db;
    private $mysoc;

    public function __construct($db, $mysoc) {
        $this->db = $db;
        $this->mysoc = $mysoc;
    }

    public function getInvoicesByMonth($month, $year) {
        $invoices = [];
        $start = dol_mktime(0, 0, 0, $month, 1, $year);
        $end = dol_time_plus_duree($start, 1, 'm');

        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."facture
        WHERE datef >= '".$this->db->idate($start)."'
        AND datef < '".$this->db->idate($end)."'";

        $resql = $this->db->query($sql);
        while ($obj = $this->db->fetch_object($resql)) {
            $invoice = new Facture($this->db);
            $invoice->fetch($obj->rowid);
            $invoices[] = $invoice;
        }

        return $invoices;
    }

    public function getInvoicesByIds($ids) {
        $invoices = [];
        foreach ($ids as $id) {
            $invoice = new Facture($this->db);
            $invoice->fetch($id);
            $invoices[] = $invoice;
        }
        return $invoices;
    }

    public function buildXml($invoices) {
        $paymentTypes = array(
            "VIR" => "draft",
            "LIQ" => "cash",
        );

        // Create a new DOMDocument instance
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true; // Pretty print the XML

        $mysoc = new Societe($this->db);
        $mysoc->fetch(1); // ID 1 is usually the internal company
        $ico = $mysoc->idprof1; // or idprof2, idprof3, etc., depending on country



        // Create the root element
        $dataPack = $dom->createElement('dat:dataPack');
        $dataPack->setAttribute('xmlns:dat', 'http://www.stormware.cz/schema/version_2/data.xsd');
        $dataPack->setAttribute('xmlns:inv', 'http://www.stormware.cz/schema/version_2/invoice.xsd');
        $dataPack->setAttribute('xmlns:typ', 'http://www.stormware.cz/schema/version_2/type.xsd');
        $dataPack->setAttribute('id', 'fa001');
        $dataPack->setAttribute('note', 'Exported from dolibarr');
        $dataPack->setAttribute('ico', $this->mysoc->idprof1);
        $dataPack->setAttribute("application", "DolibarrExport");
        $dataPack->setAttribute('version', '2.0');

        // Append the root element to the DOM document
        $dom->appendChild($dataPack);

        // Iterate over invoices and add them to the XML
        foreach ($invoices as $inv) {
            // If invoice is not paid, continue
            if ($inv->paye != "1") continue;

            $inv->fetch_thirdparty();

            $dataPackItem = $dom->createElement('dat:dataPackItem');
            $dataPackItem->setAttribute('id', $inv->ref);
            $dataPackItem->setAttribute('version', '2.0');
            $dataPack->appendChild($dataPackItem);

            $invoice = $dom->createElement('inv:invoice');
            $invoice->setAttribute("version", "2.0");
            $dataPackItem->appendChild($invoice);

            $invoiceHeader = $dom->createElement('inv:invoiceHeader');
            $invoice->appendChild($invoiceHeader);

            $invoiceType = $dom->createElement('inv:invoiceType', 'issuedInvoice');
            $invoiceHeader->appendChild($invoiceType);

            $number = $dom->createElement('inv:number');
            $numberRequested = $dom->createElement('typ:numberRequested', $inv->ref);
            $number->appendChild($numberRequested);
            $invoiceHeader->appendChild($number);

            // Format the date
            $date = $dom->createElement('inv:date', date('Y-m-d', $inv->date));
            $invoiceHeader->appendChild($date);

            // Payment type
            $paymentType = $dom->createElement('inv:paymentType');
            $paymentTypeType = $dom->createElement('typ:paymentType', $paymentTypes[$inv->mode_reglement_code]);
            $paymentType->appendChild($paymentTypeType);
            $invoiceHeader->appendChild($paymentType);

            // Account number
            $account = $dom->createElement('inv:account');
            $accountNo = $dom->createElement('typ:accountNo', '2702946879');
            $account->appendChild($accountNo);
            $bankCode = $dom->createElement('typ:bankCode', '2010');
            $account->appendChild($bankCode);
            $invoiceHeader->appendChild($account);

            // My identity
            $myIdentity = $dom->createElement('inv:myIdentity');
            $myAddress = $dom->createElement('typ:address');

            $myAddress->appendChild($dom->createElement('typ:name', $this->mysoc->name));
            if (!empty($this->mysoc->lastname)) {
                $myAddress->appendChild($dom->createElement('typ:surname', $this->mysoc->lastname));
            }
            $myAddress->appendChild($dom->createElement('typ:city', $this->mysoc->town));
            $myAddress->appendChild($dom->createElement('typ:street', $this->mysoc->address));
            $myAddress->appendChild($dom->createElement('typ:number', $this->mysoc->zip)); // if needed separately
            $myAddress->appendChild($dom->createElement('typ:zip', $this->mysoc->zip));
            $myAddress->appendChild($dom->createElement('typ:ico', $this->mysoc->idprof1));

            $myIdentity->appendChild($myAddress);
            $invoiceHeader->appendChild($myIdentity);

            // Partners identity
            $thirdparty = $inv->thirdparty;

            $partnerIdentity = $dom->createElement('inv:partnerIdentity');
            $partnerAddress = $dom->createElement('typ:address');

            $partnerAddress->appendChild($dom->createElement('typ:company', $thirdparty->name));
            $partnerAddress->appendChild($dom->createElement('typ:city', $thirdparty->town));
            $partnerAddress->appendChild($dom->createElement('typ:street', $thirdparty->address));
            if (!empty($thirdparty->phone)) {
                $partnerAddress->appendChild($dom->createElement('typ:mobilPhone', $thirdparty->phone));
            }
            if (!empty($thirdparty->tva_intra)) {
                $partnerAddress->appendChild($dom->createElement('typ:dic', $thirdparty->tva_intra));
            }
            if (!empty($thirdparty->idprof1)) {
                $partnerAddress->appendChild($dom->createElement('typ:ico', $thirdparty->idprof1));
            }

            $partnerIdentity->appendChild($partnerAddress);
            $invoiceHeader->appendChild($partnerIdentity);

            // Add public note (description)
            $text = $dom->createElement('inv:text', $inv->note_public);
            $invoiceHeader->appendChild($text);

            // Create invoice detail
            $invoiceDetail = $dom->createElement('inv:invoiceDetail');
            $invoice->appendChild($invoiceDetail);

            // Iterate over invoice lines
            foreach ($inv->lines as $line) {
                $invoiceItem = $dom->createElement('inv:invoiceItem');
                $invoiceDetail->appendChild($invoiceItem);

                $rawText = $line->product_label ?: ($line->desc ?: $line->ref);
                $decodedText = html_entity_decode($rawText, ENT_QUOTES | ENT_XML1, 'UTF-8');

                $lineText = $dom->createElement('inv:text');
                $lineText->appendChild($dom->createTextNode($decodedText));
                $invoiceItem->appendChild($lineText);

                $quantity = $dom->createElement('inv:quantity', $line->qty);
                $invoiceItem->appendChild($quantity);

                $rateVAT = $dom->createElement('inv:rateVAT', 'none');
                $invoiceItem->appendChild($rateVAT);

                $homeCurrency = $dom->createElement('inv:homeCurrency');
                $unitPrice = $dom->createElement('typ:unitPrice', price2num($line->subprice, 'MT'));
                $homeCurrency->appendChild($unitPrice);
                $invoiceItem->appendChild($homeCurrency);

                $payVAT = $dom->createElement('inv:payVAT', 'false');
                $invoiceItem->appendChild($payVAT);

                $discountPercentage = $dom->createElement('inv:discountPercentage', '0.0');
                $invoiceItem->appendChild($discountPercentage);
            }
        }

        // Return the XML as a string
        return $dom->saveXML();
    }
}
