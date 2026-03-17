<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Config\Repository as Config;
use Illuminate\Redis\RedisManager;
use Illuminate\Support\Facades\DB;
use Modules\Core\Exceptions\ApiQuotaExceededException;
use Predis\Client as RedisClient;

/**
 * Gestisce le quote giornaliere delle API carrier per evitare
 * costi eccessivi. Usa Redis come contatore primario e PostgreSQL
 * per la persistenza dei contatori giornalieri.
 */
class ApiQuotaManager
{
    private const REDIS_KEY_PREFIX = 'api_quota';
    private const REDIS_TTL = 86400; // 24 ore

    private float $warningThreshold;
    private float $blockThreshold;

    public function __construct(
        private readonly RedisManager $redis,
        private readonly Config $config,
    ) {
        $this->warningThreshold = (float) $config->get('core.api_quota.warning_threshold', 0.80);
        $this->blockThreshold   = (float) $config->get('core.api_quota.block_threshold', 0.95);
    }

    /**
     * Verifica se una chiamata API è consentita.
     * Per chiamate non-critiche, blocca se quota >95% esaurita.
     * Per chiamate critiche, avvisa ma lascia passare sempre.
     *
     * @throws ApiQuotaExceededException se la quota è esaurita e la chiamata non è critica
     */
    public function checkAndIncrement(string $carrier, string $callType, bool $isCritical = false): void
    {
        $dailyLimit = $this->getDailyLimit($carrier, $callType);

        if ($dailyLimit === 0) {
            return; // Nessun limite configurato
        }

        $currentCount = $this->getCurrentCount($carrier, $callType);
        $usageRatio   = $currentCount / $dailyLimit;

        if (!$isCritical && $usageRatio >= $this->blockThreshold) {
            throw new ApiQuotaExceededException(
                carrier: $carrier,
                callType: $callType,
                currentCount: $currentCount,
                dailyLimit: $dailyLimit,
            );
        }

        // Incrementa il contatore Redis
        $this->increment($carrier, $callType);

        // Persist async in DB (non-bloccante)
        $this->persistToDatabase($carrier, $callType, $dailyLimit);
    }


    /**
     * Verifica se una chiamata API è consentita (senza incrementare).
     * Per chiamate non-critiche: false se quota >= 95%.
     * Per chiamate critiche (activation, deactivation, ticket_open): sempre true.
     */
    public function canCall(string , string ): bool
    {
         = ->getDailyLimit(, );

        if ( === 0) {
            return true;
        }

         = ->getCurrentCount(, );

        return ( / ) < ->blockThreshold;
    }

    /**
     * Incrementa il contatore dopo una chiamata andata a buon fine.
     * Usare insieme a canCall() nel pattern:
     *   if (->canCall(, )) { ... }
     *   ->consume(, );
     */
    public function consume(string , string ): void
    {
         = ->getDailyLimit(, );
        ->increment(, );
        ->persistToDatabase(, , );
    }

    /**
     * Verifica se un callType è critico (activation, deactivation, ticket_open).
     * Le chiamate critiche non vengono bloccate dalla quota.
     */
    public function isCritical(string , string ): bool
    {
         = ->config->get("core.api_quota.carriers.{}.critical", []);
        return in_array(, , true);
    }

    /**
     * Ritorna le chiamate rimanenti per oggi.
     */
    public function remaining(string , string ): int
    {
        return ->getRemainingCalls(, );
    }

    /**
     * Ritorna un riepilogo uso per tutti i carrier/tipi (alias di getAllQuotaStatus).
     *
     * @return array<string, array<string, mixed>>
     */
    public function getUsageSummary(): array
    {
        return ->getAllQuotaStatus();
    }

    /**
     * Ritorna il contatore attuale per carrier/callType oggi.
     */
    public function getCurrentCount(string $carrier, string $callType): int
    {
        $key = $this->buildKey($carrier, $callType);

        return (int) $this->redis->connection()->get($key) ?? 0;
    }

    /**
     * Ritorna lo stato completo di tutte le quote.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getAllQuotaStatus(): array
    {
        $carriers = $this->config->get('core.api_quota.carriers', []);
        $status   = [];

        foreach ($carriers as $carrier => $carrierConfig) {
            $status[$carrier] = [];
            foreach ($carrierConfig['call_types'] as $callType) {
                $current    = $this->getCurrentCount($carrier, $callType);
                $limit      = $carrierConfig['daily_limit'];
                $ratio      = $limit > 0 ? round($current / $limit, 4) : 0;

                $status[$carrier][$callType] = [
                    'current'     => $current,
                    'limit'       => $limit,
                    'ratio'       => $ratio,
                    'percentage'  => round($ratio * 100, 2),
                    'warning'     => $ratio >= $this->warningThreshold,
                    'blocked'     => $ratio >= $this->blockThreshold,
                    'date'        => now()->toDateString(),
                ];
            }
        }

        return $status;
    }

    /**
     * Resetta il contatore Redis (solo admin, per emergenze).
     */
    public function resetCounter(string $carrier, string $callType): void
    {
        $key = $this->buildKey($carrier, $callType);
        $this->redis->connection()->del($key);
    }

    /**
     * Verifica se un carrier è vicino al limite (per dashboard/alerting).
     */
    public function isNearLimit(string $carrier, string $callType): bool
    {
        $limit   = $this->getDailyLimit($carrier, $callType);
        $current = $this->getCurrentCount($carrier, $callType);

        if ($limit === 0) {
            return false;
        }

        return ($current / $limit) >= $this->warningThreshold;
    }

    /**
     * Ritorna il numero di chiamate rimanenti per oggi.
     */
    public function getRemainingCalls(string $carrier, string $callType): int
    {
        $limit   = $this->getDailyLimit($carrier, $callType);
        $current = $this->getCurrentCount($carrier, $callType);

        return max(0, $limit - $current);
    }

    private function increment(string $carrier, string $callType): void
    {
        $key  = $this->buildKey($carrier, $callType);
        $pipe = $this->redis->connection()->pipeline();
        $pipe->incr($key);
        $pipe->expire($key, $this->getRemainingSecondsToday());
        $pipe->execute();
    }

    private function persistToDatabase(string $carrier, string $callType, int $dailyLimit): void
    {
        // Upsert in PostgreSQL per storico/reportistica
        DB::table('api_quota_usage')->upsert(
            [
                'carrier'      => $carrier,
                'call_type'    => $callType,
                'date'         => now()->toDateString(),
                'count'        => $this->getCurrentCount($carrier, $callType),
                'daily_limit'  => $dailyLimit,
                'updated_at'   => now(),
            ],
            uniqueBy: ['carrier', 'call_type', 'date'],
            update: ['count', 'daily_limit', 'updated_at'],
        );
    }

    private function buildKey(string $carrier, string $callType): string
    {
        return sprintf(
            '%s:%s:%s:%s',
            self::REDIS_KEY_PREFIX,
            $carrier,
            $callType,
            now()->toDateString()
        );
    }

    private function getDailyLimit(string $carrier, string $callType): int
    {
        $carriers = $this->config->get('core.api_quota.carriers', []);

        return (int) ($carriers[$carrier]['daily_limit'] ?? 0);
    }

    private function getRemainingSecondsToday(): int
    {
        return (int) now()->endOfDay()->diffInSeconds(now());
    }
}
