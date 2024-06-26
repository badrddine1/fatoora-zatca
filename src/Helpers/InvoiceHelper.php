<?php

namespace Bl\FatooraZatca\Helpers;

use Bl\FatooraZatca\Objects\InvoiceItem;

class InvoiceHelper
{
    /**
     * calculate item sub total.
     *
     * @param  \Bl\FatooraZatca\Objects\InvoiceItem $item
     * @return float
     */
    public function calculateSubTotal(InvoiceItem $item): float
    {
        return $item->total - $item->tax;
        // return ($item['price'] * $item['quantity']) - $item['discount'];
    }

    /**
     * get the signing time.
     *
     * @param  object $invoice
     * @return string
     */
    public function getSigningTime(object $invoice): string
    {
        // TODO : must send the date of signing time when post simplified invoice.
        return "{$invoice->invoice_date}T{$invoice->invoice_time}Z";
    }

    /**
     * get the timestamp of invoice.
     *
     * @param  object $invoice
     * @return string
     */
    public function getTimestamp(object $invoice): string
    {
        return "{$invoice->invoice_date}T{$invoice->invoice_time}Z";
    }

    /**
     * get the hashed certificate in base64 format.
     * note : certificate parameter is in base64 format.
     *
     * @param  mixed $certificate
     * @return string
     */
    public function getHashedCertificate(string $certificate): string
    {
        $certificate = base64_decode($certificate);

        $certificate = hash('sha256', $certificate, false);

        return base64_encode($certificate);
    }

    /**
     * get the certificate signature from certificate output.
     *
     * @param  mixed $certificate_output
     * @return string
     */
    public function getCertificateSignature(array $certificate_output): string
    {
        $signature = unpack('H*', $certificate_output['signature'])['1'];

        return pack('H*', substr($signature, 2));
    }
}
