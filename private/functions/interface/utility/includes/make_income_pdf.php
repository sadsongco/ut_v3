<?php

require (base_path('functions/shop/make_order_pdf.php'));

class INCOME_PDF extends FPDF {
    const LILAC = [100, 100, 100];
    const GREY = [220, 220, 220];
    const BLACK = [0, 0, 0];
    const ITEM_GREY = [80, 80, 80];
    const HEADER_POS = [10, 10];
    const DATE_POS = [120, 28];
    const ADDRESS_POS = [120, 37];
    const ORDER_NO_POS = [25, 77];
    const ITEM_POS = [25, 83, 145];
    const PRICE_X = -30;
    private $pw;

    public function Init ($income) {
    $this->SetTitle("Unbelievable Truth income for period ".$income["period"]);
    $this->SetSubject("Unbelievable Truth income for period ".$income["period"]);
    $this->SetAuthor("Nigel Powell");
    $this->AddFont('opensansbold','', 'OpenSans-Bold.php');
    $this->AddFont('opensansregular', '', 'OpenSans-Regular.php');
    $this->pw = $this->GetPageWidth();
    }

        function Header () {
        $logo_url =base_path("assets/images/logo/ut-logo-black.png");
        $w = 100;
        $this->SetX(($this->pw-$w)/2);
        $this->Image($logo_url, null, null, $w, 0, 'PNG', 'https://unbelievabletruth.co.uk');
    }

    function Footer () {
        $this->SetFont('opensansregular', '', 9);
        $h = 15;
        $this->SetY(-$h);
        $this->setFillColor(...self::GREY);
        $address = "Unbelievable Truth, 52 Claremont Road, Rugby, CV21 3LX, UK";
        $email = "info@unbelievabletruth.co.uk";
        $this->SetTextColor(...self::BLACK);
        $this->Cell(0, $h, $address." :: ".$email, 'T', 0, 'C', true, 'mailto:info@unbelievabletruth.co.uk');
    }

    private function PeriodCell($order_no) {
        $this->SetFont('opensansbold', '', 12);
        $this->SetTextColor(...self::BLACK);
        $this->SetXY(...self::ORDER_NO_POS);
        $this->Cell(0, 8, "Income for period ".$order_no, 0, 1);
    }
    
    private function SubTotalCell($subtotal) {
        $this->setFont('opensansbold', '', 12);
        $this->SetX(self::ITEM_POS[0]);
        $this->Cell(0, 0, "Subtotal", 0, 0, 'L');
        $this->setFont('opensansregular', '', 11);
        $this->SetX(self::PRICE_X);
        $this->Cell(0, 0, GBP.$subtotal, 0, 1, 'R');
    }

    private function ShippingCell($shipping_cost) {
        $this->SetX(self::ITEM_POS[0]);
        $this->Cell(0, 0, "Postage and packing", 0, 0, 'L');
        $this->SetX(self::PRICE_X);
        $this->Cell(0, 0, GBP.$shipping_cost, 0, 1, 'R');
    }

    private function VatCell($vat) {
        $this->SetX(self::ITEM_POS[0]);
        $this->Cell(0, 0, "including VAT charged", 0, 0, 'L');
        $this->SetX(self::PRICE_X);
        $this->Cell(0, 0, GBP.$vat, 0, 1, 'R');
    }

    private function VatExemptSubtotalCell($vat_exempt_subtotal) {
        $this->SetX(self::ITEM_POS[0]);
        $this->Cell(0, 0, "International VAT exempt subtotal", 0, 0, 'L');
        $this->SetX(self::PRICE_X);
        $this->Cell(0, 0, GBP.$vat_exempt_subtotal, 0, 1, 'R');
    }

    private function VatExemptShippingCell($vat_exempt_shipping) {
        $this->SetX(self::ITEM_POS[0]);
        $this->Cell(0, 0, "International VAT exempt shipping", 0, 0, 'L');
        $this->SetX(self::PRICE_X);
        $this->Cell(0, 0, GBP.$vat_exempt_shipping, 0, 1, 'R');
    }

    private function TotalCell($total) {
        $this->SetX(self::ORDER_NO_POS[0]);
        $this->setFont('opensansbold', '', 16);
        $this->setTextColor(...self::BLACK);
        $this->SetDrawColor(...self::LILAC);
        $this->Cell(0, 8, "TOTAL", 'T', 0, 'L');
        $this->SetX(self::PRICE_X);
        $this->Cell(0, 10, GBP.$total, 'T', 1, 'R');
    }


    private function Spacer($height = 10) {
     $this->Cell(0, $height, '', 0, 1);   
    }

    public function IncomeDetailsCell ($income) {
        $this->PeriodCell($income["period"]);
        $this->SetXY(...self::ITEM_POS);
        $this->Spacer();
        $this->SubTotalCell($income['subtotal'], 2);
        $this->Spacer();
        $this->ShippingCell($income['shipping'], 2);
        $this->Spacer();
        if ($income['vat']) {
            $this->VatCell($income['vat'], 2);
            $this->Spacer();
        }
        $this->VatExemptSubtotalCell($income['vat_exempt_subtotal'], 2);
        $this->Spacer();
        $this->VatExemptShippingCell($income['vat_exempt_shipping'], 2);
        $this->Spacer();
        $this->TotalCell($income['total'], 2);
        $this->Spacer(20);
    }
}

function makeIncomePDF($income, $output = 'D', $path = '') {
    $pdf = new INCOME_PDF();
    $pdf->Init($income);
    $pdf->AddPage();
    $pdf->IncomeDetailsCell($income);
    $save_path = "Unbelievable_Truth_order_".str_replace(" ", "_", $income["period"]).".pdf";
    $pdf->Output($output, $path. $save_path);
    return $save_path;
}

?>