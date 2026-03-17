<?php

declare(strict_types=1);

use Modules\Coverage\Services\AddressNormalizer;

describe('AddressNormalizer', function () {

    beforeEach(function () {
        $this->normalizer = new AddressNormalizer();
    });

    // ---- normalizeVia ----

    it('espande abbreviazione V.LE in Viale', function () {
        expect($this->normalizer->normalizeVia('V.LE DELLE TERME'))
            ->toBe('Viale Delle Terme');
    });

    it('espande C.SO in Corso', function () {
        expect($this->normalizer->normalizeVia('C.SO VITTORIO EMANUELE'))
            ->toBe('Corso Vittorio Emanuele');
    });

    it('espande P.ZZA in Piazza', function () {
        expect($this->normalizer->normalizeVia('P.ZZA GARIBALDI'))
            ->toBe('Piazza Garibaldi');
    });

    it('rimuove diacritici da via', function () {
        expect($this->normalizer->normalizeVia('VIA DELLA LIBERTÀ'))
            ->toBe('Via Della Liberta');
    });

    it('gestisce già Via corretta', function () {
        expect($this->normalizer->normalizeVia('Via Roma'))
            ->toBe('Via Roma');
    });

    it('comprime spazi multipli', function () {
        expect($this->normalizer->normalizeVia('VIA   DEI   MILLE'))
            ->toBe('Via Dei Mille');
    });

    it('espande STR. in Strada', function () {
        expect($this->normalizer->normalizeVia('STR. PROVINCIALE 12'))
            ->toBe('Strada Provinciale 12');
    });

    // ---- normalizeCivico ----

    it('normalizza civico 3/A', function () {
        expect($this->normalizer->normalizeCivico('3/A'))
            ->toBe('3A');
    });

    it('normalizza civico 3 A con spazio', function () {
        expect($this->normalizer->normalizeCivico('3 A'))
            ->toBe('3A');
    });

    it('rimuove zeri iniziali', function () {
        expect($this->normalizer->normalizeCivico('010'))
            ->toBe('10');
    });

    it('normalizza civico con trattino', function () {
        expect($this->normalizer->normalizeCivico('3-A'))
            ->toBe('3A');
    });

    it('gestisce civico numerico semplice', function () {
        expect($this->normalizer->normalizeCivico('42'))
            ->toBe('42');
    });

    // ---- normalizeComune ----

    it('normalizza comune in title case', function () {
        expect($this->normalizer->normalizeComune('NAPOLI'))
            ->toBe('Napoli');
    });

    it('rimuove diacritici dal comune', function () {
        expect($this->normalizer->normalizeComune('SANLURI'))
            ->toBe('Sanluri');
    });

    // ---- normalizeProvincia ----

    it('normalizza provincia a maiuscolo 2 lettere', function () {
        expect($this->normalizer->normalizeProvincia('ba'))
            ->toBe('BA');
    });

    it('tronca a 2 caratteri', function () {
        expect($this->normalizer->normalizeProvincia('Milano'))
            ->toBe('MI');
    });

    // ---- normalizeAddress ----

    it('normalizza un indirizzo completo', function () {
        $result = $this->normalizer->normalizeAddress(
            'V.LE DELLA LIBERTÀ',
            '3/A',
            'BARI',
            'ba'
        );

        expect($result['via'])->toBe('Viale Della Liberta')
            ->and($result['civico'])->toBe('3A')
            ->and($result['comune'])->toBe('Bari')
            ->and($result['provincia'])->toBe('BA');
    });
});
