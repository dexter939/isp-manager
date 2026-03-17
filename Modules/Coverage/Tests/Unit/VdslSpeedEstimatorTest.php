<?php

declare(strict_types=1);

use Modules\Coverage\Services\FeasibilityService;
use Modules\Coverage\Services\AddressNormalizer;

describe('VDSL2 Speed Estimator', function () {

    beforeEach(function () {
        $this->service = new FeasibilityService(new AddressNormalizer());
    });

    /**
     * Formula: Vmax = Vnom * e^(-0.0023 * distanza_metri)
     * Testa i valori di riferimento da CLAUDE_CONTEXT.md
     */
    it('stima correttamente a 200m (attenuazione bassa)', function () {
        // 100 * e^(-0.0023 * 200) = 100 * e^(-0.46) ≈ 63 Mbps
        $result = $this->callPrivateMethod('estimateVdslSpeed', [200.0, 100]);
        expect($result)->toBeBetween(60, 70);
    });

    it('stima correttamente a 500m', function () {
        // 100 * e^(-0.0023 * 500) = 100 * e^(-1.15) ≈ 32 Mbps
        $result = $this->callPrivateMethod('estimateVdslSpeed', [500.0, 100]);
        expect($result)->toBeBetween(28, 36);
    });

    it('stima correttamente a 1000m', function () {
        // 100 * e^(-0.0023 * 1000) = 100 * e^(-2.3) ≈ 10 Mbps
        $result = $this->callPrivateMethod('estimateVdslSpeed', [1000.0, 100]);
        expect($result)->toBeBetween(8, 12);
    });

    it('non ritorna mai meno di 1 Mbps', function () {
        // A 5000m la formula darebbe quasi 0 — deve ritornare almeno 1
        $result = $this->callPrivateMethod('estimateVdslSpeed', [5000.0, 100]);
        expect($result)->toBeGreaterThanOrEqual(1);
    });

    it('ritorna velocità nominale a distanza zero', function () {
        $result = $this->callPrivateMethod('estimateVdslSpeed', [0.0, 100]);
        expect($result)->toBe(100);
    });
})->with(function () {
    // Helper per chiamare metodo privato
})->tap(function ($test) {
    // Aggiunge helper al test
    $test->callPrivateMethod = function (string $method, array $args) use ($test) {
        $reflection = new ReflectionClass(FeasibilityService::class);
        $m = $reflection->getMethod($method);
        $m->setAccessible(true);
        return $m->invokeArgs($test->service, $args);
    };
});

// Approccio alternativo con reflection diretta
it('verifica formula VDSL2 tramite reflection', function () {
    $service = new FeasibilityService(new AddressNormalizer());

    $method = new ReflectionMethod(FeasibilityService::class, 'estimateVdslSpeed');
    $method->setAccessible(true);

    // 100 Mbps a 500m → atteso ~32 Mbps
    $result = $method->invoke($service, 500.0, 100);
    expect($result)->toBeBetween(28, 36);

    // 200 Mbps a 300m → atteso ~200 * e^(-0.69) ≈ ~100 Mbps
    $result200 = $method->invoke($service, 300.0, 200);
    expect($result200)->toBeBetween(90, 120);
});
