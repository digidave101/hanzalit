<?php
/**
 * Shared payment / shipping-term helpers for quotation Excel + PDF exports.
 */

const TI_DEFAULT_LC_TERMS =
    "Letter of Credit due 10 days from the time an order is placed for 100% of the value of the order. Corporate Policy requires the Letter of Credit to contain or include the following:\n\n"
    . "i.     Irrevocable Letter of Credit, TRANSFERABLE\n"
    . "ii.    Payment at Sight\n"
    . "iii.   Confirmed\n"
    . "iv.    Place of expiry is United States\n"
    . "v.     Issued in USD\n"
    . "vi.    Presentation of documents restricted to the counters of TD BANK USA";

function tiIsExWorks(string $incoterm): bool {
    $t = strtoupper(trim($incoterm));
    return $t !== '' && (str_starts_with($t, 'EXW') || str_contains($t, 'EX WORKS'));
}

function tiWirePaymentText(string $wireOption): string {
    switch ($wireOption) {
        case '50_50':
            return '50% of order value due via wire transfer at the time the order is placed. 50% 2 weeks prior to shipment due via wire transfer.';
        case '100':
            return '100% of the order value due via wire transfer at the time the order is placed.';
        case 'none':
            return '';
        case '30_70':
        default:
            return '30% of order value due via wire transfer at the time the order is placed. 70% 2 weeks prior to shipment due via wire transfer.';
    }
}

/**
 * Normalize payment settings from a request/save body (or already-normalized array).
 * @return array{wire:string,include_lc:bool,lc_terms:string}
 */
function tiNormalizePaymentTerms(array $body): array {
    $wire = trim((string)($body['payment_wire'] ?? $body['wire'] ?? '30_70'));
    if (!in_array($wire, ['30_70', '50_50', '100', 'none'], true)) {
        $wire = '30_70';
    }
    $includeLc = array_key_exists('payment_include_lc', $body)
        ? !empty($body['payment_include_lc'])
        : (array_key_exists('include_lc', $body) ? !empty($body['include_lc']) : false);
    if ($wire === 'none') {
        $includeLc = true;
    }
    $lcTerms = trim((string)($body['payment_lc_terms'] ?? $body['lc_terms'] ?? ''));
    if ($includeLc && $lcTerms === '') {
        $lcTerms = TI_DEFAULT_LC_TERMS;
    }
    return [
        'wire' => $wire,
        'include_lc' => $includeLc,
        'lc_terms' => $lcTerms,
    ];
}

/**
 * For EXW quotes, ocean freight must not appear (not even TBD).
 * @param array $shipEstimates
 * @param string $incoterm
 * @return array
 */
function tiShipEstimatesForExport(array $shipEstimates, string $incoterm): array {
    if (tiIsExWorks($incoterm)) {
        return [];
    }
    return $shipEstimates;
}
