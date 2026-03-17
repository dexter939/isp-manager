<?php
namespace Modules\Infrastructure\TopologyDiscovery\Services;
use Illuminate\Support\Facades\Log;
class LldpDiscoveryScanner {
    // LLDP-MIB::lldpRemTable OID base
    private const LLDP_REM_TABLE_OID = '1.0.8802.1.1.2.1.4';
    public function scan(object $device): array {
        if (config('app.carrier_mock', false)) {
            return $this->mockLldpResponse($device);
        }
        if (empty($device->snmp_community) || empty($device->ip_address)) {
            Log::warning("LldpDiscoveryScanner: device {$device->id} missing SNMP config");
            return [];
        }
        try {
            // SNMP walk on LLDP remote table
            $result = snmp2_real_walk($device->ip_address, $device->snmp_community, self::LLDP_REM_TABLE_OID, 3000000, 3);
            return $this->parseLldpRemTable($result, $device);
        } catch (\Throwable $e) {
            Log::warning("LldpDiscoveryScanner: SNMP failed for {$device->ip_address}: {$e->getMessage()}");
            return [];
        }
    }
    private function parseLldpRemTable(array $snmpResult, object $device): array {
        $neighbors = [];
        $parsed    = [];
        foreach ($snmpResult as $oid => $value) {
            // OID format: .1.0.8802.1.1.2.1.4.1.1.{subId}.{timeFilter}.{localPortNum}.{index}
            $parts = explode('.', ltrim($oid, '.'));
            if (count($parts) < 4) continue;
            $subId     = (int)$parts[count($parts)-4];
            $localPort = (int)$parts[count($parts)-2];
            $index     = (int)$parts[count($parts)-1];
            $key = "{$localPort}_{$index}";
            $parsed[$key][$subId] = $value;
        }
        foreach ($parsed as $key => $fields) {
            $neighbors[] = ['source_device_id'=>$device->id,'source_interface'=>"port_{$key}",'target_mac'=>$this->formatMac($fields[7] ?? ''),'target_ip'=>null,'target_hostname'=>$fields[5] ?? null,'discovery_method'=>'lldp'];
        }
        return $neighbors;
    }
    private function formatMac(string $raw): string {
        $hex = strtolower(preg_replace('/[^0-9a-fA-F]/', '', bin2hex($raw)));
        return implode(':', str_split(str_pad($hex, 12, '0', STR_PAD_LEFT), 2));
    }
    private function mockLldpResponse(object $device): array {
        return [['source_device_id'=>$device->id,'source_interface'=>'ether1','target_mac'=>'aa:bb:cc:dd:ee:01','target_ip'=>null,'target_hostname'=>'mock-neighbor','discovery_method'=>'lldp']];
    }
    public function matchMacToDevice(string $mac): ?object {
        $normalized = strtolower(preg_replace('/[^0-9a-f]/', '', $mac));
        return \Illuminate\Support\Facades\DB::table('hardware_devices')->where(function ($q) use ($normalized, $mac) {
            $q->whereRaw("lower(regexp_replace(mac_address, '[^0-9a-fA-F]', '', 'g')) = ?", [$normalized])->orWhere('mac_address', $mac);
        })->first();
    }
}
