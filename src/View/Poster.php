<?php

declare(strict_types=1);

namespace App\View;

/**
 * Illustration d'un logement façon affiche de voyage, dessinée en SVG à partir de son type
 * (chalet, villa…) et de son cadre (mer, montagne…). Chaque logement varie un peu selon son identifiant.
 */
final class Poster
{
    private const PALETTES = [
        'mer' => ['sky' => ['#8ec9e8', '#f7e3b5'], 'sun' => '#f2b134'],
        'montagne' => ['sky' => ['#a9cbe8', '#eaf2f8'], 'sun' => '#f6c453'],
        'campagne' => ['sky' => ['#f3cf7a', '#fbefd0'], 'sun' => '#fff4d6'],
        'lac' => ['sky' => ['#b9d7ea', '#f3e3c3'], 'sun' => '#f2b134'],
        'ville' => ['sky' => ['#e9876b', '#fbd3a0'], 'sun' => '#fde7b0'],
    ];

    private const GROUND = ['mer' => 268, 'montagne' => 262, 'campagne' => 266, 'lac' => 262, 'ville' => 266];

    /**
     * @param string $label Description pour les lecteurs d'écran, ou vide si l'image est décorative.
     */
    public static function render(string $type, string $setting, int $seed, string $label = '', bool $wide = false): string
    {
        $setting = isset(self::PALETTES[$setting]) ? $setting : 'campagne';
        $palette = self::PALETTES[$setting];
        $id = 'p' . $seed . substr(md5($type . $setting . ($wide ? 'w' : '')), 0, 6);
        $variation = ($seed * 37) % 100;
        $sunX = 70 + ($variation * 2.6);
        $houseX = 110 + (($seed * 53) % 170);

        $accessibility = $label !== ''
            ? sprintf('role="img" aria-label="%s"', htmlspecialchars($label, ENT_QUOTES))
            : 'aria-hidden="true" focusable="false"';
        $aspect = $wide ? 'xMidYMax slice' : 'xMidYMid meet';

        return sprintf(
            '<svg class="poster" viewBox="0 0 400 300" preserveAspectRatio="%s" %s xmlns="http://www.w3.org/2000/svg">'
            . '<defs><linearGradient id="%s" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="%s"/>'
            . '<stop offset="1" stop-color="%s"/></linearGradient></defs>'
            . '<rect width="400" height="300" fill="url(#%s)"/>%s%s%s</svg>',
            $aspect,
            $accessibility,
            $id,
            $palette['sky'][0],
            $palette['sky'][1],
            $id,
            self::sun($setting, $sunX, $palette['sun']),
            self::landscape($setting, $variation),
            self::house($type, $houseX, self::GROUND[$setting], $setting),
        );
    }

    private static function sun(string $setting, float $x, string $color): string
    {
        $y = $setting === 'ville' ? 196 : ($setting === 'montagne' ? 78 : 104);
        $radius = $setting === 'ville' ? 46 : 32;

        return sprintf('<circle cx="%.0f" cy="%d" r="%d" fill="%s"/>', $x, $y, $radius, $color);
    }

    private static function landscape(string $setting, int $v): string
    {
        $shift = $v % 30;

        return match ($setting) {
            'mer' => sprintf(
                '<path d="M%d 192 Q%d 150 %d 164 T430 158 V194 Z" fill="#4e7a5a"/>'
                . '<rect y="190" width="400" height="110" fill="#1e5aa8"/>'
                . '<path d="M0 212 q15 -5 30 0 t30 0 t30 0 t30 0 t30 0 t30 0 t30 0 t30 0 t30 0 t30 0 t30 0 t30 0 t30 0 t30 0" fill="none" stroke="#3a7fc4" stroke-width="3"/>'
                . '<path d="M0 236 q15 -5 30 0 t30 0 t30 0 t30 0 t30 0 t30 0 t30 0 t30 0 t30 0 t30 0 t30 0 t30 0 t30 0 t30 0" fill="none" stroke="#3a7fc4" stroke-width="3"/>'
                . '<path d="M0 262 Q120 246 240 262 T400 258 V300 H0 Z" fill="#e9c46a"/>',
                240 + $shift,
                280 + $shift,
                330 + $shift,
            ),
            'montagne' => sprintf(
                '<polygon points="0,200 70,%d 130,170 200,90 270,160 330,%d 400,170 400,300 0,300" fill="#5b7da6"/>'
                . '<polygon points="200,90 184,112 194,106 200,116 207,106 216,112" fill="#fff"/>'
                . '<path d="M0 232 Q100 204 200 226 T400 220 V300 H0 Z" fill="#2e6b50"/>'
                . '<path d="M0 256 Q150 240 300 258 T400 252 V300 H0 Z" fill="#4f8a5b"/>%s',
                118 + $shift,
                104 + $shift,
                self::pines([28, 56, 318, 352, 380], 238, 30),
            ),
            'campagne' => sprintf(
                '<path d="M0 202 Q80 %d 170 196 T340 186 T430 190 V300 H0 Z" fill="#a7c66b"/>'
                . '<path d="M0 232 Q200 206 400 228 V300 H0 Z" fill="#d9b44a"/>'
                . '<path d="M0 246 Q200 222 400 244 M0 258 Q200 236 400 256" fill="none" stroke="#c49b33" stroke-width="3"/>'
                . '<path d="M0 266 Q200 252 400 266 V300 H0 Z" fill="#7fa650"/>%s',
                168 + $shift,
                self::cypresses([34, 58, 338, 366], 230),
            ),
            'lac' => sprintf(
                '<polygon points="0,192 90,%d 160,176 240,112 320,166 400,140 400,200 0,200" fill="#6c7faf"/>'
                . '<rect y="190" width="400" height="60" fill="#2c6e91"/>'
                . '<path d="M30 206 H120 M200 214 H330 M80 226 H190 M260 236 H370" stroke="#5d9cc0" stroke-width="3"/>'
                . '<path d="M0 246 Q200 232 400 246 V300 H0 Z" fill="#6e9e58"/>%s',
                130 + $shift,
                self::pines([24, 362, 386], 248, 26),
            ),
            default => '<path d="M0 200 H34 V158 H70 V176 H104 V140 H140 V182 H176 V150 H214 V170 H250 V134 H290 V178 H330 V152 H366 V172 H400 V266 H0 Z" fill="#4a5a80"/>'
                . self::windows()
                . '<rect y="262" width="400" height="38" fill="#39445e"/>'
                . '<path d="M20 281 H60 M100 281 H140 M180 281 H220 M260 281 H300 M340 281 H380" stroke="#f2c86b" stroke-width="3"/>',
        };
    }

    /**
     * @param list<int> $xs
     */
    private static function pines(array $xs, int $base, int $height): string
    {
        $svg = '';
        foreach ($xs as $x) {
            $svg .= sprintf(
                '<polygon points="%d,%d %d,%d %d,%d" fill="#1f4d3a"/>',
                $x,
                $base - $height,
                $x - (int) ($height * 0.35),
                $base,
                $x + (int) ($height * 0.35),
                $base,
            );
        }

        return $svg;
    }

    /**
     * @param list<int> $xs
     */
    private static function cypresses(array $xs, int $base): string
    {
        $svg = '';
        foreach ($xs as $x) {
            $svg .= sprintf('<ellipse cx="%d" cy="%d" rx="7" ry="26" fill="#2e5e3e"/>', $x, $base - 22);
        }

        return $svg;
    }

    private static function windows(): string
    {
        $svg = '';
        foreach ([[44, 170], [80, 186], [114, 152], [150, 192], [186, 162], [224, 180], [262, 146], [300, 188], [340, 164], [376, 182]] as [$x, $y]) {
            $svg .= sprintf('<rect x="%d" y="%d" width="8" height="10" fill="#f2c86b"/>', $x, $y);
        }

        return $svg;
    }

    private static function house(string $type, int $x, int $base, string $setting): string
    {
        $snow = $setting === 'montagne';

        return match ($type) {
            'appartement' => self::apartment($x, $base),
            'chalet' => self::chalet($x, $base, $snow),
            'villa' => self::villa($x, $base),
            'cabane' => self::cabin($x, $base),
            default => self::cottage($x, $base),
        };
    }

    private static function cottage(int $x, int $b): string
    {
        return sprintf(
            '<rect x="%d" y="%d" width="12" height="26" fill="#9e3f2e"/>'
            . '<rect x="%d" y="%d" width="100" height="60" fill="#f6efe6"/>'
            . '<polygon points="%d,%d %d,%d %d,%d" fill="#c8553d"/>'
            . '<rect x="%d" y="%d" width="20" height="34" rx="2" fill="#1e3a5f"/>'
            . '<rect x="%d" y="%d" width="18" height="16" fill="#f2c86b"/><rect x="%d" y="%d" width="18" height="16" fill="#f2c86b"/>',
            $x + 22,
            $b - 100,
            $x - 50,
            $b - 60,
            $x - 60,
            $b - 58,
            $x,
            $b - 106,
            $x + 60,
            $b - 58,
            $x - 10,
            $b - 34,
            $x - 38,
            $b - 48,
            $x + 20,
            $b - 48,
        );
    }

    private static function chalet(int $x, int $b, bool $snow): string
    {
        $planks = '';
        for ($y = $b - 50; $y < $b; $y += 10) {
            $planks .= sprintf('<path d="M%d %d H%d" stroke="#74482f" stroke-width="2"/>', $x - 55, $y, $x + 55);
        }
        $snowLine = $snow ? sprintf('<polyline points="%d,%d %d,%d %d,%d" fill="none" stroke="#fff" stroke-width="6" stroke-linejoin="round"/>', $x - 70, $b - 57, $x, $b - 110, $x + 70, $b - 57) : '';

        return sprintf(
            '<rect x="%d" y="%d" width="110" height="58" fill="#8b5a3c"/>%s'
            . '<polygon points="%d,%d %d,%d %d,%d" fill="#5a3a28"/>%s'
            . '<rect x="%d" y="%d" width="90" height="6" fill="#5a3a28"/>'
            . '<path d="M%d %d V%d M%d %d V%d M%d %d V%d M%d %d V%d" stroke="#5a3a28" stroke-width="3"/>'
            . '<rect x="%d" y="%d" width="18" height="18" fill="#f2c86b"/><rect x="%d" y="%d" width="18" height="18" fill="#f2c86b"/>'
            . '<rect x="%d" y="%d" width="20" height="30" fill="#3b2418"/>',
            $x - 55,
            $b - 58,
            $planks,
            $x - 72,
            $b - 55,
            $x,
            $b - 112,
            $x + 72,
            $b - 55,
            $snowLine,
            $x - 45,
            $b - 42,
            $x - 40,
            $b - 42,
            $b - 52,
            $x - 15,
            $b - 42,
            $b - 52,
            $x + 15,
            $b - 42,
            $b - 52,
            $x + 40,
            $b - 42,
            $b - 52,
            $x - 12,
            $b - 84,
            $x - 40,
            $b - 32,
            $x + 14,
            $b - 30,
        );
    }

    private static function villa(int $x, int $b): string
    {
        return sprintf(
            '<polygon points="%d,%d %d,%d %d,%d %d,%d" fill="#4cc3d9"/>'
            . '<rect x="%d" y="%d" width="70" height="36" fill="#f4f4f4"/><rect x="%d" y="%d" width="74" height="6" fill="#2b3a55"/>'
            . '<rect x="%d" y="%d" width="116" height="52" fill="#fff"/><rect x="%d" y="%d" width="120" height="6" fill="#2b3a55"/>'
            . '<rect x="%d" y="%d" width="56" height="30" fill="#8cc9e6"/><path d="M%d %d V%d M%d %d V%d" stroke="#fff" stroke-width="3"/>'
            . '<rect x="%d" y="%d" width="24" height="34" fill="#1e3a5f"/>'
            . '<rect x="%d" y="%d" width="44" height="18" fill="#8cc9e6"/>',
            $x - 92,
            $b + 6,
            $x + 62,
            $b + 6,
            $x + 74,
            $b + 22,
            $x - 80,
            $b + 22,
            $x - 24,
            $b - 86,
            $x - 26,
            $b - 90,
            $x - 66,
            $b - 52,
            $x - 68,
            $b - 56,
            $x - 56,
            $b - 40,
            $x - 38,
            $b - 40,
            $b - 10,
            $x - 20,
            $b - 40,
            $b - 10,
            $x + 14,
            $b - 34,
            $x - 12,
            $b - 78,
        );
    }

    private static function apartment(int $x, int $b): string
    {
        $windows = '';
        foreach ([0, 1, 2, 3] as $row) {
            foreach ([0, 1, 2] as $col) {
                $lit = ($row + $col) % 3 === 0;
                $windows .= sprintf(
                    '<rect x="%d" y="%d" width="14" height="16" fill="%s"/>',
                    $x - 31 + $col * 24,
                    $b - 120 + $row * 26,
                    $lit ? '#f2c86b' : '#8cc9e6',
                );
            }
            $windows .= sprintf('<path d="M%d %d H%d" stroke="#c8553d" stroke-width="3"/>', $x - 36, $b - 102 + $row * 26, $x + 36);
        }

        return sprintf(
            '<rect x="%d" y="%d" width="80" height="132" fill="#f2e6d8"/><rect x="%d" y="%d" width="86" height="7" fill="#c8553d"/>%s'
            . '<rect x="%d" y="%d" width="18" height="18" fill="#1e3a5f"/>',
            $x - 40,
            $b - 132,
            $x - 43,
            $b - 138,
            $windows,
            $x - 9,
            $b - 18,
        );
    }

    private static function cabin(int $x, int $b): string
    {
        return sprintf(
            '<rect x="%d" y="%d" width="6" height="34" fill="#6b4630"/><rect x="%d" y="%d" width="6" height="34" fill="#6b4630"/>'
            . '<path d="M%d %d L%d %d M%d %d L%d %d" stroke="#6b4630" stroke-width="3"/>'
            . '<rect x="%d" y="%d" width="96" height="7" fill="#6b4630"/>'
            . '<polygon points="%d,%d %d,%d %d,%d" fill="#8b5a3c"/>'
            . '<polygon points="%d,%d %d,%d %d,%d" fill="#f6efe6"/>'
            . '<circle cx="%d" cy="%d" r="8" fill="#f2c86b"/><rect x="%d" y="%d" width="14" height="22" fill="#3b2418"/>',
            $x - 34,
            $b - 34,
            $x + 28,
            $b - 34,
            $x - 31,
            $b,
            $x + 31,
            $b - 30,
            $x + 31,
            $b,
            $x - 31,
            $b - 30,
            $x - 48,
            $b - 40,
            $x - 42,
            $b - 40,
            $x,
            $b - 120,
            $x + 42,
            $b - 40,
            $x - 26,
            $b - 40,
            $x,
            $b - 90,
            $x + 26,
            $b - 40,
            $x,
            $b - 68,
            $x - 7,
            $b - 62,
        );
    }
}
