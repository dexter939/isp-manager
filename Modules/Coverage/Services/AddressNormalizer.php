<?php

declare(strict_types=1);

namespace Modules\Coverage\Services;

/**
 * Normalizza indirizzi stradali italiani per uniformare la ricerca
 * tra la banca dati FiberCop NetMap e i dati inseriti dagli operatori.
 *
 * Applica in ordine:
 * 1. Maiuscolo/minuscolo canonico
 * 2. Espansione abbreviazioni toponomastiche
 * 3. Rimozione diacritici (à→a, è→e, ...)
 * 4. Rimozione caratteri speciali
 * 5. Single-spacing e trim
 */
class AddressNormalizer
{
    /**
     * Mappa abbreviazioni → forma canonica (case-insensitive input).
     * @var array<string, string>
     */
    private const TOPONYM_MAP = [
        'V/'         => 'Via ',
        'V. '        => 'Via ',
        'V.LE '      => 'Viale ',
        'VLE '       => 'Viale ',
        'VIALE '     => 'Viale ',
        'C.SO '      => 'Corso ',
        'CSO '       => 'Corso ',
        'CORSO '     => 'Corso ',
        'P.ZZA '     => 'Piazza ',
        'PZA '       => 'Piazza ',
        'P.ZA '      => 'Piazza ',
        'PIAZZA '    => 'Piazza ',
        'P.LE '      => 'Piazzale ',
        'PIAZZALE '  => 'Piazzale ',
        'LGO '       => 'Largo ',
        'L.GO '      => 'Largo ',
        'LARGO '     => 'Largo ',
        'STR. '      => 'Strada ',
        'STR '       => 'Strada ',
        'STRADA '    => 'Strada ',
        'S.DA '      => 'Strada ',
        'LOC. '      => 'Localita ',
        'LOC '       => 'Localita ',
        'LOCALITA\'' => 'Localita ',
        'LOCALITA '  => 'Localita ',
        'FRAZ. '     => 'Frazione ',
        'FRAZ '      => 'Frazione ',
        'FRAZIONE '  => 'Frazione ',
        'REGIONE '   => 'Regione ',
        'REG. '      => 'Regione ',
        'VIA '       => 'Via ',
        'VICOLO '    => 'Vicolo ',
        'VCO '       => 'Vicolo ',
        'V.CO '      => 'Vicolo ',
        'TRAVERSA '  => 'Traversa ',
        'TRAV. '     => 'Traversa ',
        'CONTRADA '  => 'Contrada ',
        'C.DA '      => 'Contrada ',
        'BORGATA '   => 'Borgata ',
        'B.TA '      => 'Borgata ',
        'VICO '      => 'Vico ',
        'SALITA '    => 'Salita ',
        'DISCESA '   => 'Discesa ',
        'SCALINATA ' => 'Scalinata ',
    ];

    /**
     * Mappa caratteri accentati → ASCII.
     */
    private const DIACRITICS_MAP = [
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
        'ý' => 'y', 'ÿ' => 'y',
        'ñ' => 'n',
        'ç' => 'c',
        'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A',
        'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
        'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
        'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
        'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
        'Ç' => 'C', 'Ñ' => 'N',
    ];

    /**
     * Normalizza una via stradale.
     *
     * @param string $via Es: "V.LE DELLA LIBERTÀ" → "Viale Della Liberta"
     */
    public function normalizeVia(string $via): string
    {
        $via = trim($via);
        $via = strtoupper($via);

        // Espandi abbreviazioni toponomastiche
        foreach (self::TOPONYM_MAP as $abbr => $canonical) {
            if (str_starts_with($via, $abbr)) {
                $via = $canonical . substr($via, strlen($abbr));
                break;
            }
        }

        // Rimuovi diacritici
        $via = strtr($via, self::DIACRITICS_MAP);

        // Rimuovi caratteri non alfanumerici eccetto spazio, apostrofo, punto, trattino
        $via = preg_replace('/[^A-Z0-9 \'\.\-\/]/i', '', $via);

        // Comprimi spazi multipli
        $via = preg_replace('/\s+/', ' ', $via);

        return trim($via);
    }

    /**
     * Normalizza un numero civico.
     *
     * @param string $civico Es: "3/A" → "3A", "3 A" → "3A", "010" → "10"
     */
    public function normalizeCivico(string $civico): string
    {
        $civico = trim(strtoupper($civico));

        // Rimuovi spazi interni tra numero e lettera: "3 A" → "3A"
        $civico = preg_replace('/^(\d+)\s+([A-Z])$/', '$1$2', $civico);

        // Normalizza separatori: "3/A" → "3A", "3-A" → "3A"
        $civico = preg_replace('/^(\d+)[\/\-]([A-Z])$/', '$1$2', $civico);

        // Rimuovi zeri iniziali: "010" → "10"
        $civico = ltrim($civico, '0') ?: '0';

        // Rimuovi caratteri non validi
        $civico = preg_replace('/[^0-9A-Z\/\-]/', '', $civico);

        return $civico;
    }

    /**
     * Normalizza un comune.
     *
     * @param string $comune Es: "MILANO" → "Milano", "sant'agata" → "Sant'Agata"
     */
    public function normalizeComune(string $comune): string
    {
        $comune = trim($comune);
        $comune = strtr($comune, self::DIACRITICS_MAP);
        $comune = preg_replace('/\s+/', ' ', $comune);

        // Title case preservando apostrofi: Sant'Agata → Sant'Agata
        return mb_convert_case($comune, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Normalizza una provincia a sigla 2 lettere maiuscole.
     *
     * @param string $provincia Es: "mi" → "MI", "Milano" → "MI" (solo se nella mappa)
     */
    public function normalizeProvincia(string $provincia): string
    {
        return strtoupper(trim(substr($provincia, 0, 2)));
    }

    /**
     * Normalizza un indirizzo completo e ritorna un array con i campi normalizzati.
     *
     * @return array{via: string, civico: string, comune: string, provincia: string}
     */
    public function normalizeAddress(string $via, string $civico, string $comune, string $provincia): array
    {
        return [
            'via'       => $this->normalizeVia($via),
            'civico'    => $this->normalizeCivico($civico),
            'comune'    => $this->normalizeComune($comune),
            'provincia' => $this->normalizeProvincia($provincia),
        ];
    }

    /**
     * Calcola la somiglianza tra due vie normalizzate (0.0 - 1.0).
     * Utile per fuzzy matching quando la ricerca esatta non trova risultati.
     */
    public function similarity(string $via1, string $via2): float
    {
        $via1 = $this->normalizeVia($via1);
        $via2 = $this->normalizeVia($via2);

        similar_text($via1, $via2, $percent);

        return round($percent / 100, 4);
    }
}
