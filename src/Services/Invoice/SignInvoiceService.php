<?php

namespace Bl\FatooraZatca\Services\Invoice;

use Bl\FatooraZatca\Actions\GetXmlFileAction;
use Bl\FatooraZatca\Helpers\InvoiceHelper;
use Bl\FatooraZatca\Transformers\PriceFormat;
use Bl\FatooraZatca\Transformers\PublicKey;
use DOMDocument;
use DOMXPath;
use phpseclib3\File\X509;
use SimpleXMLElement;

class SignInvoiceService
{
    /**
     * the digital signature for zatca in base64 format.
     *
     * @var string
     */
    protected $digitalSignature;

    /**
     * the certificate output.
     *
     * @var array
     */
    protected $certificateOutput;

    /**
     * the issuer name.
     *
     * @var string
     */
    protected $issuerName;

    /**
     * the public key.
     *
     * @var string
     */
    protected $publicKey;

    /**
     * the seller data.
     *
     * @var object
     */
    protected $seller;

    /**
     * the invoice data.
     *
     * @var object
     */
    protected $invoice;

    /**
     * the invoice xml content.
     *
     * @var string
     */
    protected $invoiceXml;

    /**
     * the hashed invoice in base64 format.
     *
     * @var string
     */
    protected $invoiceHash;

    /**
     * __construct
     *
     * @param  object $seller
     * @param  string $invoice_hash
     * @return void
     */
    public function __construct(object $seller, object $invoice, string $invoice_xml, string $invoice_hash)
    {
        $this->seller       = $seller;

        $this->invoice      = $invoice;

        $this->invoiceXml   = $invoice_xml;

        $this->invoiceHash  = $invoice_hash;
    }

    /**
     * generate the signed invoice in base64 format.
     *
     * @return string
     */
    public function generate(): string
    {
        $this->setUp();

        $this->invoiceXml = str_replace('SET_XML_ENCODING', '<?xml version="1.0" encoding="UTF-8"?>', $this->invoiceXml);

        $this->invoiceXml = str_replace(
            'SET_UBL_EXTENSIONS_FOR_SIGNED',
            $this->getUBLExtensions(),
            $this->invoiceXml
        );

        $this->invoiceXml = str_replace(
            'SET_QR_AND_SIGNATURE_FOR_SIGNED',
            $this->getQRCodeData(),
            $this->invoiceXml
        );

        ///////////////////////
        $xml = new DOMDocument("1.0", "utf-8");
        // $xml->preserveWhiteSpace = false;
        // $xml->formatOutput = false;
        // $linearizedXml = preg_replace('/\s+/', '', $doc->saveXML());

        $xml->loadXML($this->invoiceXml); // invoice file after populate the properties;
        // dd($xml);
        //use domPath to register this namespace
        $xpath = new DOMXPath($xml);
        // register namespace
        $xpath->registerNamespace('default-ns', "urn:oasis:names:specification:ubl:schema:xsd:Invoice-2");
        $xpath->registerNamespace('sig', "urn:oasis:names:specification:ubl:schema:xsd:CommonSignatureComponents-2");
        $xpath->registerNamespace('sac', "urn:oasis:names:specification:ubl:schema:xsd:SignatureAggregateComponents-2");
        $xpath->registerNamespace('sbc', "urn:oasis:names:specification:ubl:schema:xsd:SignatureBasicComponents-2");
        $xpath->registerNamespace('ds', "http://www.w3.org/2000/09/xmldsig#");
        $xpath->registerNamespace('xades', "http://uri.etsi.org/01903/v1.3.2#");

        // path of SignedProperties
        $SignedProperties = "//default-ns:Invoice/ext:UBLExtensions/ext:UBLExtension/ext:ExtensionContent/sig:UBLDocumentSignatures/sac:SignatureInformation/ds:Signature/ds:Object/xades:QualifyingProperties/xades:SignedProperties";
        // get SignedProperties by path query
        $SignedPropertiesValue = $xpath->query($SignedProperties);
        // convert SignedProperties node to c14n standerd.
        $canonicalizationInvoiceXML = $SignedPropertiesValue[0]->C14N(\true);
        $canonicalizationInvoiceXML = '                                ' . $canonicalizationInvoiceXML;
        // $canonicalizationInvoiceXML = preg_replace('/\s+/', '', $canonicalizationInvoiceXML);

        // dd($canonicalizationInvoiceXML, $this->invoiceXml);

        // $canonicalizationInvoiceXML = str_replace('></ds:DigestMethod>', '/>', $canonicalizationInvoiceXML);
        // dd(base64_encode(hash('sha256', $canonicalizationInvoiceXML)));
        $canonicalizationInvoiceXML = base64_encode(hash('sha256', $canonicalizationInvoiceXML));
        ///////////////////////////////
        $this->invoiceXml = str_replace('SET_SIGNED_PROPERTIES_HASH', $canonicalizationInvoiceXML, $this->invoiceXml);


        return base64_encode($this->invoiceXml);
    }

    /**
     * setUp data used in this service.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $csrX509 = "-----BEGIN CERTIFICATE-----\r\n". base64_decode($this->seller->certificate) ."\r\n-----END CERTIFICATE-----";

        $x509 = new X509();

        $this->certificateOutput = $x509->loadX509($csrX509);

        // $this->issuerName        = $x509->getIssuerDN(X509::DN_STRING);

        $issuerNameArray = $x509->getIssuerDN(X509::DN_ARRAY)['rdnSequence'];
        $CN = $issuerNameArray[3][0]['value']['printableString'];
        $DC1 = $issuerNameArray[2][0]['value']['ia5String'];
        $DC2 = $issuerNameArray[1][0]['value']['ia5String'];
        $DC3 = $issuerNameArray[0][0]['value']['ia5String'];

        $this->issuerName = "CN={$CN}, DC={$DC1}, DC={$DC2}, DC={$DC3}";

        $this->publicKey         = (new PublicKey)->transform($x509->getPublicKey());

        $this->digitalSignature = $this->getDigitalSignature();
    }

    /**
     * get the digital signature in base64 format.
     *
     * @return string
     */
    protected function getDigitalSignature(): string
    {
        openssl_sign(

            base64_decode($this->invoiceHash),

            $signature,

            base64_decode($this->seller->private_key),

            'sha256'
        );

        return base64_encode($signature);
    }

    /**
     * get the UBL extensions xml content.
     *
     * @return string
     */
    protected function getUBLExtensions(): string
    {
        $xml = GetXmlFileAction::handle('xml_ubl_extensions');

        $xml = str_replace('SET_INVOICE_HASH', $this->invoiceHash, $xml);

        // $xml = str_replace('SET_SIGNED_PROPERTIES_HASH', $this->getSignedPropertiesHash(), $xml);

        $xml = str_replace('SET_DIGITAL_SIGNATURE', $this->digitalSignature, $xml);

        $xml = str_replace('SET_CERTIFICATE_VALUE', base64_decode($this->seller->certificate), $xml);

        $xml = str_replace('SET_CERTIFICATE_SIGNED_PROPERTIES', $this->getSignedProperties(), $xml);

        return rtrim($xml, "\n");
    }

    /**
     * get the signed properties.
     *
     * @return string
     */
    protected function getSignedProperties(): string
    {
        // generate xml ubl signed properties...
        $xml = GetXmlFileAction::handle('xml_ubl_signed_properties');

        $xml = str_replace('SET_SIGN_TIMESTAMP', (new InvoiceHelper)->getSigningTime($this->invoice), $xml);

        $xml = str_replace('SET_CERTIFICATE_HASH', (new InvoiceHelper)->getHashedCertificate($this->seller->certificate), $xml);

        $xml = str_replace('SET_CERTIFICATE_ISSUER', $this->issuerName, $xml);

        $issuerSerialNumber = $this->certificateOutput['tbsCertificate']['serialNumber']->toString();

        $xml = str_replace('SET_CERTIFICATE_SERIAL_NUMBER', $issuerSerialNumber, $xml);
        // dd($xml);
        return $xml;
    }

    /**
     * get the signed properties hash.
     *
     * @return string
     */
    protected function getSignedPropertiesHash(): string
    {
        $xml = GetXmlFileAction::handle('xml_ubl_signed_properties_hash');

        $xml = str_replace('SET_SIGN_TIMESTAMP', (new InvoiceHelper)->getSigningTime($this->invoice), $xml);

        $xml = str_replace('SET_CERTIFICATE_HASH', (new InvoiceHelper)->getHashedCertificate($this->seller->certificate), $xml);

        $xml = str_replace('SET_CERTIFICATE_ISSUER', $this->issuerName, $xml);

        $issuerSerialNumber = $this->certificateOutput['tbsCertificate']['serialNumber']->toString();

        $xml = str_replace('SET_CERTIFICATE_SERIAL_NUMBER', $issuerSerialNumber, $xml);

        // hash the signed properties...
        // $signedProperties = unpack('H*', $xml)['1'];
        // $signedProperties = hash('sha256', $signedProperties);

        $doc = new DOMDocument();
        // $doc->preserveWhiteSpace = false;
        // $doc->formatOutput = true;
        $doc->loadXML($xml);
        // $doc->normalize();
        // $doc->C14N();
        // // dd($doc->saveXML());
        // $xml = preg_replace('/\s+/', '', $doc->saveXML());
        /*
        $xml = str_replace('<?xmlversion="1.0"?>', '', $xml);
        */
// dd($xml);
        $signedProperties = hash('sha256', $xml);

        // encode hashed signed properties in base64 format...
        return base64_encode($signedProperties);
    }

    /**
     * get QR code data for stage 2 of zatca.
     *
     * @return string
     */
    protected function getQRCodeData(): string
    {
        $xml = GetXmlFileAction::handle('xml_qr_and_signature');

        $data = [
            $this->seller->registration_name,
            $this->seller->tax_number,
            (new InvoiceHelper)->getTimestamp($this->invoice),
            PriceFormat::transform($this->invoice->total),
            PriceFormat::transform($this->invoice->tax),
            $this->invoiceHash,
            $this->digitalSignature,
            $this->publicKey,
            (new InvoiceHelper)->getCertificateSignature($this->certificateOutput),
        ];

        $tlvEncoded = (new TLVProtocolService($data))->toBase64Format();

        $xml = str_replace('SET_QR_CODE_DATA', $tlvEncoded, $xml);

        $xml = rtrim($xml, "\n");

        return $xml;
    }
}
