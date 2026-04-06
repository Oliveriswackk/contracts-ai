<?php
// ============================================================
//  OCR Wait Screen — php test_animation.php
// ============================================================

// ── ANSI helpers ─────────────────────────────────────────────
function cls()          { echo "\033[2J\033[H"; }
function move($r, $c)   { echo "\033[{$r};{$c}H"; }
function hide()         { echo "\033[?25l"; }
function show()         { echo "\033[?25h"; }
function save()         { echo "\033[s"; }
function restore()      { echo "\033[u"; }

// colors
function fg($code, $s)  { return "\033[{$code}m{$s}\033[0m"; }
function bold($s)       { return "\033[1m{$s}\033[0m"; }

const C_RESET   = "\033[0m";
const C_BOLD    = "\033[1m";
const C_DIM     = "\033[2m";

// 256-color fg/bg
function fg256($n, $s)  { return "\033[38;5;{$n}m{$s}\033[0m"; }
function bg256($n, $s)  { return "\033[48;5;{$n}m{$s}" . C_RESET; }

// ── Terminal width ────────────────────────────────────────────
function termWidth(): int {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $out = shell_exec('mode con');
        if ($out && preg_match('/Columns[^:]*:\s*(\d+)/i', $out, $m)) {
            return max(60, min((int)$m[1], 120));
        }
        return 100;
    }
    $w = (int) shell_exec('tput cols 2>/dev/null');
    return max(60, min($w ?: 80, 120));
}

// ── Cat frames ───────────────────────────────────────────────
// Each frame: [line0, line1, line2, tail_char]
const FRAMES = [
    [" /\\_/\\  ", "( o.o ) ", " > ^ <  ", "~"],
    [" /\\_/\\  ", "( -.o ) ", " > ^ <  ", "~"],
    [" /\\_/\\  ", "( o.o ) ", " > v <  ", " "],
    [" /\\_/\\  ", "( o.- ) ", " > ^ <  ", "~"],
    [" /\\_/\\  ", "( ^.^ ) ", " > ^ <  ", "~"],
    [" /\\_/\\  ", "( o.o ) ", " > v <  ", " "],
    [" /\\_/\\  ", "( >.< ) ", " > ^ <  ", "~"],
    [" /\\_/\\  ", "( -.- ) ", " > ^ <  ", " "],
];

const FRAMES_DONE = [
    [" /\\_/\\  ", "( o.o ) ", " > ^ <  "],
    ["  /\\_/\\ ", " ( ^.^) ", "  (  )~ "],
];

// ── Messages per progress range ──────────────────────────────
const MESSAGES = [
    [0,  15, "iniciando OCR..."],
    [15, 30, "cargando modelo..."],
    [30, 45, "detectando texto..."],
    [45, 58, "leyendo caracteres..."],
    [58, 70, "esto toma tiempo..."],
    [70, 82, "procesando paginas..."],
    [82, 92, "casi listo, juro..."],
    [92, 99, "no te desesperes..."],
    [99,101, "finalizando..."],
];

function getMessage(int $pct): string {
    foreach (MESSAGES as [$lo, $hi, $msg]) {
        if ($pct >= $lo && $pct < $hi) return $msg;
    }
    return "procesando...";
}

// ── Speech bubble ─────────────────────────────────────────────
// Draws a box above the cat's head at column $x
function drawBubble(int $row, int $x, string $msg, int $termW): void {
    $len  = mb_strlen($msg);
    $bw   = $len + 4;                     // inner width + 2 padding + 2 border
    $bx   = max(1, min($x, $termW - $bw));// clamp to screen

    // top border
    move($row,     $bx); echo fg256(244, "╭" . str_repeat("─", $len + 2) . "╮");
    // text
    move($row + 1, $bx); echo fg256(244, "│ ") . fg256(252, $msg) . fg256(244, " │");
    // bottom border
    move($row + 2, $bx); echo fg256(244, "╰" . str_repeat("─", $len + 2) . "╯");
    // arrow pointing down-left toward the cat
    $arrowX = $bx + 2;
    move($row + 3, $arrowX); echo fg256(244, "▼");
}

// ── Cat draw ──────────────────────────────────────────────────
function drawCat(int $catRow, int $x, array $frame, int $termW): void {
    $color = 81; // cyan-ish
    foreach ($frame as $i => $line) {
        $safeX = max(1, min($x, $termW - 9));
        move($catRow + $i, $safeX);
        echo fg256($color, $line);
    }
}

function drawBar(int $row, int $pct, int $termW): void {
    $label      = " OCR ";
    $pctStr     = str_pad($pct, 3) . "%";
    $suffix     = " " . $pctStr . " ";
    $labelLen   = mb_strlen($label);
    $suffixLen  = mb_strlen($suffix);
    $avail      = $termW - $labelLen - $suffixLen - 2;
    $avail      = max(10, $avail);

    $filled = (int) round($avail * $pct / 100);
    $empty  = $avail - $filled;

    if ($pct < 30)      $barColor = 33;
    elseif ($pct < 70)  $barColor = 214;
    elseif ($pct < 90)  $barColor = 82;
    else                $barColor = 46;

    $bar = fg256($barColor, str_repeat("\xe2\x96\x88", $filled))
         . fg256(238,       str_repeat("\xe2\x96\x91", $empty));

    move($row, 1);
    echo C_BOLD . fg256(252, $label) . C_RESET
       . fg256(244, "[") . $bar . fg256(244, "]")
       . C_BOLD . fg256($barColor, $suffix) . C_RESET;

    move($row + 1, 1);
    $phase = getMessage($pct);
    echo fg256(244, str_repeat(" ", $labelLen + 1))
       . fg256(240, "\xe2\x96\xb8 ") . fg256(246, $phase)
       . str_repeat(" ", 25);
}

// ── Spinner ───────────────────────────────────────────────────
const SPINNER = ['⠋','⠙','⠹','⠸','⠼','⠴','⠦','⠧','⠇','⠏'];
function drawSpinner(int $row, int $termW, int $tick): void {
    $s = SPINNER[$tick % count(SPINNER)];
    move($row, $termW - 4);
    echo fg256(81, $s);
}

// ── Stats row ────────────────────────────────────────────────
function drawStats(int $row, float $elapsed, int $pct): void {
    $eta = $pct > 0 ? round($elapsed * (100 - $pct) / $pct) : 0;
    $t   = gmdate("i:s", (int)$elapsed);
    $e   = $pct > 0 ? gmdate("i:s", $eta) : "--:--";
    move($row, 1);
    echo fg256(240, "  elapsed: ") . fg256(246, $t)
       . fg256(240, "   eta: ")    . fg256(246, $e)
       . str_repeat(" ", 10);
}

// ── Done screen ───────────────────────────────────────────────
function drawDone(int $termW, float $elapsed): void {
    cls();

    $box = [
        fg256(46,  "\xe2\x95\x94" . str_repeat("\xe2\x95\x90", 34) . "\xe2\x95\x97"),
        fg256(46,  "\xe2\x95\x91") . fg256(82, C_BOLD . "    \xe2\x9c\x94  OCR COMPLETADO           " . C_RESET) . fg256(46, "\xe2\x95\x91"),
        fg256(46,  "\xe2\x95\x9a" . str_repeat("\xe2\x95\x90", 34) . "\xe2\x95\x9d"),
    ];

    $stats = [
        "  " . fg256(252, "Tiempo total : ") . fg256(46,  gmdate("i:s", (int)$elapsed)),
        "  " . fg256(252, "Caracteres   : ") . fg256(46,  number_format(rand(1200, 9999))),
        "  " . fg256(252, "Paginas      : ") . fg256(46,  (string)rand(3, 24)),
        "  " . fg256(252, "Confianza    : ") . fg256(46,  rand(91, 99) . "%"),
        "",
        "  " . fg256(244, "Archivo guardado en output/resultado.txt"),
    ];

    $row = 2;
    foreach ($box as $line) {
        move($row++, 3); echo $line;
    }
    $row++;
    foreach ($stats as $line) {
        move($row++, 1); echo $line;
    }

    $cat = FRAMES_DONE[1];
    $cx  = max(1, (int)(($termW - 10) / 2));
    $row += 2;
    move($row,     $cx); echo fg256(220, $cat[0]);
    move($row + 1, $cx); echo fg256(220, $cat[1]);
    move($row + 2, $cx); echo fg256(220, $cat[2]);
    move($row + 4, 1);   echo "";
}

// ── Main ──────────────────────────────────────────────────────
function run(): void {
    $termW    = termWidth();
    $total    = 100;
    $catX     = 4;
    $dir      = 1;
    $frameIdx = 0;
    $tick     = 0;

    // Layout rows
    $rowBubble = 2;   // bubble top (3 lines + arrow = 4 lines)
    $rowCat    = 6;   // cat top (3 lines)
    $rowBar    = 11;
    $rowStats  = 13;
    $rowTip    = 15;

    $maxCatX   = $termW - 12;
    $minCatX   = 4;

    $startTime = microtime(true);

    cls();
    hide();
    ob_implicit_flush(true);

    // Static tip
    move($rowTip, 1);
    echo fg256(238, "  ctrl+c para cancelar");

    for ($i = 0; $i <= $total; $i++) {

        // ── clear bubble zone (4 lines) ──
        for ($r = $rowBubble; $r <= $rowBubble + 4; $r++) {
            move($r, 1); echo str_repeat(" ", $termW);
        }
        // ── clear cat zone (3 lines) ──
        for ($r = $rowCat; $r <= $rowCat + 2; $r++) {
            move($r, 1); echo str_repeat(" ", $termW);
        }

        // ── move cat ──
        $catX += $dir * 2;
        if ($catX >= $maxCatX) { $catX = $maxCatX; $dir = -1; }
        if ($catX <= $minCatX) { $catX = $minCatX; $dir =  1; }

        // ── pick frame (cycle every 3 steps) ──
        if ($i % 3 === 0) $frameIdx++;
        $frame = FRAMES[$frameIdx % count(FRAMES)];

        // ── bubble ──
        $msg = getMessage($i);
        drawBubble($rowBubble, $catX, $msg, $termW);

        // ── cat ──
        drawCat($rowCat, $catX, array_slice($frame, 0, 3), $termW);

        // ── bar ──
        drawBar($rowBar, $i, $termW);

        // ── stats ──
        $elapsed = microtime(true) - $startTime;
        drawStats($rowStats, $elapsed, $i);

        // ── spinner ──
        drawSpinner($rowBar, $termW, $tick++);

        // speed: slow start, fast middle, slow end
        if ($i < 5)       $delay = 180000;
        elseif ($i > 95)  $delay = 200000;
        else              $delay = 70000;
        usleep($delay);
    }

    $elapsed = microtime(true) - $startTime;
    drawDone($termW, $elapsed);
    show();
    move(20, 1);
}

// catch ctrl+c gracefully
if (function_exists('pcntl_signal')) {
    pcntl_signal(SIGINT, function() {
        echo "\033[?25h\033[0m\n";
        exit(0);
    });
    pcntl_async_signals(true);
}

run();