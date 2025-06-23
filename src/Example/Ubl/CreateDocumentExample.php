<?php

namespace Klsheng\Myinvois\Example\Ubl;

use Klsheng\Myinvois\Ubl\Invoice;
use Klsheng\Myinvois\Ubl\CreditNote;
use Klsheng\Myinvois\Ubl\DebitNote;
use Klsheng\Myinvois\Ubl\RefundNote;
use Klsheng\Myinvois\Ubl\SelfBilledInvoice;
use Klsheng\Myinvois\Ubl\SelfBilledCreditNote;
use Klsheng\Myinvois\Ubl\SelfBilledDebitNote;
use Klsheng\Myinvois\Ubl\SelfBilledRefundNote;
use Klsheng\Myinvois\Ubl\Address;
use Klsheng\Myinvois\Ubl\AddressLine;
use Klsheng\Myinvois\Ubl\Country;
use Klsheng\Myinvois\Ubl\LegalEntity;
use Klsheng\Myinvois\Ubl\Contact;
use Klsheng\Myinvois\Ubl\AccountingParty;
use Klsheng\Myinvois\Ubl\Party;
use Klsheng\Myinvois\Ubl\PartyIdentification;
use Klsheng\Myinvois\Ubl\AllowanceCharge;
use Klsheng\Myinvois\Ubl\Shipment;
use Klsheng\Myinvois\Ubl\Delivery;
use Klsheng\Myinvois\Ubl\TaxTotal;
use Klsheng\Myinvois\Ubl\TaxScheme;
use Klsheng\Myinvois\Ubl\TaxCategory;
use Klsheng\Myinvois\Ubl\TaxSubTotal;
use Klsheng\Myinvois\Ubl\Item;
use Klsheng\Myinvois\Ubl\CommodityClassification;
use Klsheng\Myinvois\Ubl\Price;
use Klsheng\Myinvois\Ubl\ItemPriceExtension;
use Klsheng\Myinvois\Ubl\InvoiceLine;
use Klsheng\Myinvois\Ubl\CreditNoteLine;
use Klsheng\Myinvois\Ubl\DebitNoteLine;
use Klsheng\Myinvois\Ubl\AdditionalDocumentReference;
use Klsheng\Myinvois\Ubl\LegalMonetaryTotal;
use Klsheng\Myinvois\Ubl\InvoicePeriod;
use Klsheng\Myinvois\Ubl\PayeeFinancialAccount;
use Klsheng\Myinvois\Ubl\PaymentMeans;
use Klsheng\Myinvois\Ubl\PaymentTerms;
use Klsheng\Myinvois\Ubl\BillingReference;
use Klsheng\Myinvois\Ubl\PrepaidPayment;
use Klsheng\Myinvois\Ubl\TaxExchangeRate;
use Klsheng\Myinvois\Ubl\InvoiceDocumentReference;
use Klsheng\Myinvois\Ubl\Builder\XmlDocumentBuilder;
use Klsheng\Myinvois\Ubl\Builder\JsonDocumentBuilder;
use Klsheng\Myinvois\Ubl\Constant\MSICCodes;
use Klsheng\Myinvois\Ubl\Constant\InvoiceTypeCodes;

class CreateDocumentExample
{
    public function createXmlDocument($invoiceTypeCode, $id, $supplier, $supplierInfo, $customer, $customerInfo, $salesInvoice, $itemsData, $delivery,
        $includeSignature = false, $certFilePath = null, $certPrivateKeyFilePath = null, $passphrase = null, $issuerKeys = null)
    {
        $builder = new XmlDocumentBuilder();

        return $this->createBuilder($builder, $invoiceTypeCode, $id, $supplier, $supplierInfo, $customer, $customerInfo, $salesInvoice, $itemsData, $delivery,
            $includeSignature, $certFilePath, $certPrivateKeyFilePath, $passphrase, $issuerKeys);
    }

    public function createJsonDocument($invoiceTypeCode, $id, $supplier, $supplierInfo, $customer, $customerInfo, $salesInvoice, $itemsData, $delivery = null,
        $includeSignature = false, $certFilePath = null, $certPrivateKeyFilePath = null, $passphrase = null, $issuerKeys = null)
    {
        $builder = new JsonDocumentBuilder();

        return $this->createBuilder($builder, $invoiceTypeCode, $id, $supplier, $supplierInfo, $customer, $customerInfo, $salesInvoice, $itemsData, $delivery,
            $includeSignature, $certFilePath, $certPrivateKeyFilePath, $passphrase, $issuerKeys);
    }

    private function createBuilder($builder, $invoiceTypeCode, $id, $supplier, $supplierInfo, $customer, $customerInfo, $salesInvoice, $itemsData, $delivery,
        $includeSignature, $certFilePath, $certPrivateKeyFilePath, $passphrase, $issuerKeys)
    {
        $document = $this->createDocument($invoiceTypeCode, $id, $supplier, $supplierInfo, $customer, $customerInfo, $salesInvoice, $itemsData, $delivery, $includeSignature);

        $builder->setDocument($document);

        if($includeSignature) {
            $builder->setIssuerKeys($issuerKeys);
            $builder->createSignature($certFilePath, $certPrivateKeyFilePath, $passphrase);
        }

        return $builder->build();
    }

    private function getDocumentInstance($invoiceTypeCode)
    {
        switch($invoiceTypeCode) {
            case InvoiceTypeCodes::CREDIT_NOTE:
                return new CreditNote();
                break;
            case InvoiceTypeCodes::DEBIT_NOTE:
                return new DebitNote();
                break;
            case InvoiceTypeCodes::REFUND_NOTE:
                return new RefundNote();
                break;
            case InvoiceTypeCodes::SELF_BILLED_INVOICE:
                return new SelfBilledInvoice();
                break;
            case InvoiceTypeCodes::SELF_BILLED_CREDIT_NOTE:
                return new SelfBilledCreditNote();
                break;
            case InvoiceTypeCodes::SELF_BILLED_DEBIT_NOTE:
                return new SelfBilledDebitNote();
                break;
            case InvoiceTypeCodes::SELF_BILLED_REFUND_NOTE:
                return new SelfBilledRefundNote();
                break;
            default:
                return new Invoice();
                break;
        }
    }

    private function createDocument($invoiceTypeCode, $id, $supplier, $supplierInfo, $customer, $customerInfo, $salesInvoice, $itemsData, $delivery, $includeSignature)
    {
        $issueDateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $issueDateTime->modify('-1 day'); // Yesterday

        $document = $this->getDocumentInstance($invoiceTypeCode);
        $document->setId($id);
        $document->setIssueDateTime($issueDateTime);

        if($includeSignature) {
            $typeCode = $document->getInvoiceTypeCode(); // Get original type code
            $document->setInvoiceTypeCode($typeCode, '1.1'); // 1.1 is with digital signature verification
        }

        // $document = $this->setBillingReference($document);
        // $document = $this->setPrepaidPayment($document);
        $document = $this->setSupplier($document, $supplier, $supplierInfo);
        $document = $this->setCustomer($document, $customer, $customerInfo);
        // $document = $this->setDelivery($document, $delivery);
        // $document = $this->setDocumentLine($document);
        // $document = $this->setAdditionalDocumentReference($document);
        // $document = $this->setLegalMonetaryTotal($document);
        // $document = $this->setInvoicePeriod($document);
        // $document = $this->setPaymentMeans($document);
        // $document = $this->setPaymentTerms($document);
        // $document = $this->setAllowanceCharges($document);
        $document = $this->setDocumentLine($document, $itemsData);
        $document = $this->setLegalMonetaryTotal($document, $itemsData, $salesInvoice);
        $document = $this->setTaxTotal($document);
        // $document = $this->setTaxExchangeRate($document);

        return $document;
    }

    private function setBillingReference($document)
    {
        for ($i = 0; $i < 2; $i++) {
            $billingReference = new BillingReference();
            $invoiceTypeCode = $document->getInvoiceTypeCode();

            if($invoiceTypeCode == InvoiceTypeCodes::INVOICE) {
                $additionalDocumentReference = new AdditionalDocumentReference();
                $additionalDocumentReference->setId('E12345678912' . $i);
                $billingReference->setAdditionalDocumentReference($additionalDocumentReference);
            }

            if($invoiceTypeCode == InvoiceTypeCodes::CREDIT_NOTE) {
                $invoiceDocumentReference = new InvoiceDocumentReference();
                $invoiceDocumentReference->setId('INV12345' . $i);
                $invoiceDocumentReference->setUuid('00000000000000000000' . $i);

                $billingReference->setInvoiceDocumentReference($invoiceDocumentReference);
            }

            $document->addBillingReference($billingReference);
        }

        return $document;
    }

    private function setPrepaidPayment($document)
    {
        $prepaidPayment = new PrepaidPayment();
        $prepaidPayment->setId('E12345678912');
        $prepaidPayment->setPaidAmount(1.00);
        $prepaidPayment->setPaidDateTime(new \DateTime('2000-01-01 12:00:00Z'));

        $document->setPrepaidPayment($prepaidPayment);

        return $document;
    }

    private function setSupplier($document, $partyDetail, $partyInfo)
    {
        $address = new Address();
        $address->setCityName($partyInfo['address']['cityName']);
        $address->setPostalZone($partyInfo['address']['postalZone']);
        $address->setCountrySubentityCode($partyInfo['address']['stateCode']);

        $addressLine = new AddressLine();
        $addressLine->setLine($partyInfo['address']['addressLine']['line']);
        $address->addAddressLine($addressLine);

        $country = new Country();
        $country->setIdentificationCode($partyInfo['address']['countryCode']);
        $address->setCountry($country);

        $legalEntity = new LegalEntity();
        $legalEntity->setRegistrationName($partyInfo['registrationName']);

        $contact = new Contact();
        $contact->setTelephone($partyInfo['contact']['telephone']);
        $contact->setElectronicMail($partyInfo['contact']['electronicMail']);

        $supplier = new Party();

        foreach($partyDetail as $key => $value) {
        $partyIdentification = new PartyIdentification();
            $partyIdentification->setId($value, $key);
        $supplier->addPartyIdentification($partyIdentification);
        }

        $supplier->setPostalAddress($address);
        $supplier->setLegalEntity($legalEntity);
        $supplier->setContact($contact);

        $msicCode = $partyInfo['industryClassificationCode'];
        $msicCodeDesc = MSICCodes::getDescription($msicCode);
        $supplier->setIndustryClassificationCode($msicCode, $msicCodeDesc);

        $accountingParty = new AccountingParty();
        $accountingParty->setAdditionalAccountID($partyInfo['additionalAccountID']);
        $accountingParty->setParty($supplier);

        return $document->setAccountingSupplierParty($accountingParty);
    }

    private function setCustomer($document, $partyDetail, $partyInfo)
    {
        $address = new Address();
        $address->setCityName($partyInfo['address']['cityName']);
        $address->setPostalZone($partyInfo['address']['postalZone']);
        $address->setCountrySubentityCode($partyInfo['address']['stateCode']);

        $addressLine = new AddressLine();
        $addressLine->setLine($partyInfo['address']['addressLine']['line']);
        $address->addAddressLine($addressLine);

        $country = new Country();
        $country->setIdentificationCode($partyInfo['address']['countryCode']);
        $address->setCountry($country);

        $legalEntity = new LegalEntity();
        $legalEntity->setRegistrationName($partyInfo['registrationName']);

        $contact = new Contact();
        $contact->setTelephone($partyInfo['contact']['telephone']);
        $contact->setElectronicMail($partyInfo['contact']['electronicMail']);

        $customer = new Party();

        foreach($partyDetail as $key => $value) {
        $partyIdentification = new PartyIdentification();
            $partyIdentification->setId($value, $key);
        $customer->addPartyIdentification($partyIdentification);
        }

        $customer->setPostalAddress($address);
        $customer->setLegalEntity($legalEntity);
        $customer->setContact($contact);

        $accountingParty = new AccountingParty();
        $accountingParty->setParty($customer);

        return $document->setAccountingCustomerParty($accountingParty);
    }

    private function setDelivery($document, $partyDetail)
    {
        if(empty($partyDetail)) {
            return $document;
        }

        $address = new Address();
        $address->setCityName('Kuala Lumpur');
        $address->setPostalZone('50480');
        $address->setCountrySubentityCode('14');

        $addressLine = new AddressLine();
        $addressLine->setLine('Lot 66');
        $address->addAddressLine($addressLine);

        $addressLine = new AddressLine();
        $addressLine->setLine('Bangunan Merdeka');
        $address->addAddressLine($addressLine);

        $addressLine = new AddressLine();
        $addressLine->setLine('Persiaran Jaya');
        $address->addAddressLine($addressLine);

        $country = new Country();
        $country->setIdentificationCode('MYS');
        $address->setCountry($country);

        $legalEntity = new LegalEntity();
        $legalEntity->setRegistrationName('Greenz Sdn. Bhd.');

        $deliveryParty = new Party();

        foreach($partyDetail as $key => $value) {
        $partyIdentification = new PartyIdentification();
            $partyIdentification->setId($value, $key);
        $deliveryParty->addPartyIdentification($partyIdentification);
        }

        $deliveryParty->setPostalAddress($address);
        $deliveryParty->setLegalEntity($legalEntity);

        $freightAllowanceCharge = new AllowanceCharge();
        $freightAllowanceCharge->setChargeIndicator(true);
        $freightAllowanceCharge->setAllowanceChargeReason('Service charge');
        $freightAllowanceCharge->setAmount(100);

        $shipment = new Shipment();
        $shipment->setId('1234');
        $shipment->setFreightAllowanceCharge($freightAllowanceCharge);

        $delivery = new Delivery();
        $delivery->setDeliveryParty($deliveryParty);
        $delivery->setShipment($shipment);

        return $document->setDelivery($delivery);
    }

    private function setDocumentLine($document, $itemsData)
    {
        // $allowanceCharges = [];
        // $allowanceCharge = new AllowanceCharge();
        // $allowanceCharge->setChargeIndicator(false);
        // $allowanceCharge->setAllowanceChargeReason('Sample Description1');
        // $allowanceCharge->setMultiplierFactorNumeric(0.15);
        // $allowanceCharge->setAmount(100);
        // $allowanceCharges[] = $allowanceCharge;

        // $allowanceCharge = new AllowanceCharge();
        // $allowanceCharge->setChargeIndicator(true);
        // $allowanceCharge->setAllowanceChargeReason('Sample Description2');
        // $allowanceCharge->setMultiplierFactorNumeric(0.10);
        // $allowanceCharge->setAmount(100);
        // $allowanceCharges[] = $allowanceCharge;
        
        $country = new Country();
        $country->setIdentificationCode('MYS');

        $documentLines = [];

        foreach ($itemsData as $index => $itemData) {
            // Create tax total for each item
            $taxTotal = new TaxTotal();
            $taxTotal->setTaxAmount($itemData['tax_amount']);

            $taxScheme = new TaxScheme();
            $taxScheme->setId('OTH');

            $taxCategory = new TaxCategory();
            $taxCategory->setId('01');
            $taxCategory->setPercent($itemData['tax_rate']);
            $taxCategory->setTaxExemptionReason('Exempt New Means of Transport');
            $taxCategory->setTaxScheme($taxScheme);

            $taxSubTotal = new TaxSubTotal();
            $taxSubTotal->setTaxableAmount($itemData['tax_amount']);
            $taxSubTotal->setTaxAmount($itemData['tax_amount']);
            $taxSubTotal->setPercent($itemData['tax_rate']);
            $taxSubTotal->setTaxCategory($taxCategory);
            $taxTotal->addTaxSubTotal($taxSubTotal);

            // Create item for each itemData
            $item = new Item();
            $item->setDescription($itemData['description']);
            //$item->setCountry($country); // Removed by MyInvois

            $commodityClassification = new CommodityClassification();
            $commodityClassification->setItemClassificationCode($itemData['item_code'], 'CLASS');
            $item->addCommodityClassification($commodityClassification);

            // Create price and item price extension for each item
            $price = new Price();
            $price->setPriceAmount($itemData['unit_price']);

            $itemPriceExtension = new ItemPriceExtension();
            $itemPriceExtension->setAmount($itemData['line_extension_amount']);

            // Create document line for each item
            $documentLine = new InvoiceLine();
            // $documentLine->setId('1234');
            $documentLine->setId((string)($index + 1)); // Use item ID or index + 1
            $documentLine->setInvoicedQuantity($itemData['quantity']);
            $documentLine->setLineExtensionAmount($itemData['line_extension_amount']);
            // $documentLine->setAllowanceCharges($allowanceCharges);
            $documentLine->setTaxTotal($taxTotal);
            $documentLine->setItem($item);
            $documentLine->setPrice($price);
            $documentLine->setItemPriceExtension($itemPriceExtension);
            $documentLines[] = $documentLine;
        }

        return $document->setInvoiceLines($documentLines);
    }

    private function setAdditionalDocumentReference($document)
    {
        $additionalDocumentReferences = [];

        $additionalDocumentReference = new AdditionalDocumentReference();
        $additionalDocumentReference->setId('E12345678912');
        $additionalDocumentReference->setDocumentType('CustomsImportForm');
        $additionalDocumentReferences[] = $additionalDocumentReference;

        $additionalDocumentReference = new AdditionalDocumentReference();
        $additionalDocumentReference->setId('ASEAN-Australia-New Zealand FTA (AANZFTA)');
        $additionalDocumentReference->setDocumentType('FreeTradeAgreement');
        $additionalDocumentReference->setDocumentDescription('Sample Description');
        $additionalDocumentReferences[] = $additionalDocumentReference;

        $additionalDocumentReference = new AdditionalDocumentReference();
        $additionalDocumentReference->setId('E12345678912');
        $additionalDocumentReference->setDocumentType('K2');
        $additionalDocumentReferences[] = $additionalDocumentReference;

        $additionalDocumentReference = new AdditionalDocumentReference();
        $additionalDocumentReference->setId('CIF');
        $additionalDocumentReferences[] = $additionalDocumentReference;

        return $document->setAdditionalDocumentReferences($additionalDocumentReferences);
    }

    private function setLegalMonetaryTotal($document, $itemsData, $salesInvoice)
    {
        $legalMonetaryTotal = new LegalMonetaryTotal();
        $legalMonetaryTotal->setLineExtensionAmount($salesInvoice->subtotal_before_tax);
        $legalMonetaryTotal->setTaxExclusiveAmount($salesInvoice->subtotal_before_tax);
        $legalMonetaryTotal->setTaxInclusiveAmount($salesInvoice->total_amount);
        $legalMonetaryTotal->setAllowanceTotalAmount(0);
        $legalMonetaryTotal->setChargeTotalAmount(0);
        $legalMonetaryTotal->setPayableRoundingAmount($salesInvoice->rounding_adjustment);
        $legalMonetaryTotal->setPayableAmount($salesInvoice->total_amount);

        return $document->setLegalMonetaryTotal($legalMonetaryTotal);
    }

    private function setInvoicePeriod($document)
    {
        $invoicePeriod = new InvoicePeriod();
        $invoicePeriod->setStartDate(new \DateTime('2017-11-26'));
        $invoicePeriod->setEndDate(new \DateTime('2017-11-30'));
        $invoicePeriod->setDescription('Monthly');

        return $document->setInvoicePeriod($invoicePeriod);
    }

    private function setPaymentMeans($document)
    {
        $payeeFinancialAccount = new PayeeFinancialAccount();
        $payeeFinancialAccount->setId('1234567890123');

        $paymentMeans = new PaymentMeans();
        $paymentMeans->setPayeeFinancialAccount($payeeFinancialAccount);

        return $document->setPaymentMeans($paymentMeans);
    }

    private function setPaymentTerms($document)
    {
        $paymentTerms = new PaymentTerms();
        $paymentTerms->setNote('Payment method is cash');

        return $document->setPaymentTerms($paymentTerms);
    }

    private function setAllowanceCharges($document)
    {
        $allowanceCharges = [];
        $allowanceCharge = new AllowanceCharge();
        $allowanceCharge->setChargeIndicator(false);
        $allowanceCharge->setAllowanceChargeReason('Sample Description 2');
        $allowanceCharge->setAmount(100);
        $allowanceCharges[] = $allowanceCharge;

        $allowanceCharge = new AllowanceCharge();
        $allowanceCharge->setChargeIndicator(true);
        $allowanceCharge->setAllowanceChargeReason('Service charge');
        $allowanceCharge->setAmount(100);
        $allowanceCharges[] = $allowanceCharge;

        return $document->setAllowanceCharges($allowanceCharges);
    }

    private function setTaxTotal($document)
    {
        $taxTotal = new TaxTotal();
        $taxTotal->setTaxAmount(87.63);

        $taxScheme = new TaxScheme();
        $taxScheme->setId('OTH');

        $taxCategory = new TaxCategory();
        $taxCategory->setId('01');
        $taxCategory->setTaxScheme($taxScheme);

        $taxSubTotal = new TaxSubTotal();
        $taxSubTotal->setTaxableAmount(87.63);
        $taxSubTotal->setTaxAmount(87.63);
        $taxSubTotal->setTaxCategory($taxCategory);
        $taxTotal->addTaxSubTotal($taxSubTotal);

        return $document->setTaxTotal($taxTotal);
    }

    private function setTaxExchangeRate($document)
    {
        $taxExchangeRate = new TaxExchangeRate();
        $taxExchangeRate->setSourceCurrencyCode('EUR');
        $taxExchangeRate->setTargetCurrencyCode('MYR');
        $taxExchangeRate->setCalculationRate(5.07);

        return $document->setTaxExchangeRate($taxExchangeRate);
    }
}
