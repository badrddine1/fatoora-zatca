<?php

namespace Bl\FatooraZatca\Services\Compliants;

use Bl\FatooraZatca\Objects\Setting;

class SimplifiedCreditNoteCompliantService
{
    public static function verify(Setting $setting, $privateKey, $certificate, $secret)
    {
        $seller  = new \Bl\FatooraZatca\Objects\Seller(
            $setting->registrationNumber, 'King Abdulaziz Road', '1234', '1234', 'Al Amal', 'Riyadh', '12643',
            $setting->taxNumber, $setting->organizationName, $privateKey, $certificate, $secret
        );

        $invoiceType = \Bl\FatooraZatca\Classes\InvoiceType::CREDIT_NOTE;
        $paymentType = \Bl\FatooraZatca\Classes\PaymentType::CASH;

        $invoiceItems = [
            new \Bl\FatooraZatca\Objects\InvoiceItem(1, 'Product One', 1, 50, 0, 7.5, 15, 57.5),
        ];

        $invoice = new \Bl\FatooraZatca\Objects\Invoice(
            1, 'INV100', '42156fac-991b-4a12-a6f0-54c024edd29e', '2023-11-20', '20:24:00',
            $invoiceType, $paymentType, 50, 0, 7.5, 57.5, $invoiceItems, NULL, 1, NULL, null, 'SAR', 15, '2023-11-21'
        );

        return \Bl\FatooraZatca\Zatca::reportSimplifiedInvoiceCompliance($seller, $invoice);
    }
}
