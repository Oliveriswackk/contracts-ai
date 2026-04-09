<?php

namespace App\Console\Animations;

class OCRAnimation
{
    private int $termW;
    private float $startTime;
    private int $catX = 4;
    private int $dir = 1;
    private int $frameIdx = 0;
    private int $tick = 0;

    // progreso real
    private int $current = 0;
    private int $total = 1;

    // ─────────────────────────────────────
    // PUBLIC API
    // ─────────────────────────────────────
    public function start()
    {
        $this->termW = $this->termWidth();
        $this->startTime = microtime(true);

        $this->cls();
        $this->hide();

        // Render inicial
        $this->render(0);

        $this->move(15, 1);
        echo $this->fg256(238, "  ctrl+c para cancelar");
    }

    public function update(int $current, int $total)
    {
        $this->current = $current;
        $this->total   = max(1, $total);

        $pct = intval(($current / $this->total) * 100);

        $this->render($pct);
    }

    public function log(string $msg)
    {
        $this->move(17, 1);
        echo str_repeat(" ", $this->termW);
        $this->move(17, 1);
        echo $this->fg256(246, $msg);
    }

    public function finish()
    {
        $elapsed = microtime(true) - $this->startTime;
        $this->drawDone($elapsed);
        $this->show();
    }

    // ─────────────────────────────────────
    // RENDER
    // ─────────────────────────────────────
    private function render(int $pct)
    {
        $rowBubble = 2;
        $rowCat    = 6;
        $rowBar    = 11;
        $rowStats  = 13;

        // limpiar zonas
        for ($r = $rowBubble; $r <= $rowBubble + 4; $r++) {
            $this->move($r, 1); echo str_repeat(" ", $this->termW);
        }
        for ($r = $rowCat; $r <= $rowCat + 2; $r++) {
            $this->move($r, 1); echo str_repeat(" ", $this->termW);
        }

        // mover gato
        $this->catX += $this->dir * 2;
        if ($this->catX >= $this->termW - 12) { $this->catX = $this->termW - 12; $this->dir = -1; }
        if ($this->catX <= 4) { $this->catX = 4; $this->dir = 1; }

        if ($pct % 3 === 0) $this->frameIdx++;

        $frame = self::FRAMES[$this->frameIdx % count(self::FRAMES)];

        // Mensaje dinámico
        if ($this->total <= 1 && $this->current === 0) {
            $msg = "Preparando OCR...";
        } else {
            $msg = "Página {$this->current} de {$this->total}";
        }

        $this->drawBubble($rowBubble, $this->catX, $msg);
        $this->drawCat($rowCat, $this->catX, $frame);
        $this->drawBar($rowBar, $pct);

        $elapsed = microtime(true) - $this->startTime;
        $this->drawStats($rowStats, $elapsed, $pct);
        $this->drawSpinner($rowBar);

        //usleep(50000);
    }

    // ─────────────────────────────────────
    // DRAWERS
    // ─────────────────────────────────────
    private function drawBubble(int $row, int $x, string $msg)
    {
        $len = mb_strlen($msg);
        $bw  = $len + 4;
        $bx  = max(1, min($x, $this->termW - $bw));

        $this->move($row, $bx);
        echo $this->fg256(244, "╭" . str_repeat("─", $len + 2) . "╮");

        $this->move($row + 1, $bx);
        echo $this->fg256(244, "│ ") . $this->fg256(252, $msg) . $this->fg256(244, " │");

        $this->move($row + 2, $bx);
        echo $this->fg256(244, "╰" . str_repeat("─", $len + 2) . "╯");

        $this->move($row + 3, $bx + 2);
        echo $this->fg256(244, "▼");
    }

    private function drawCat(int $row, int $x, array $frame)
    {
        foreach ($frame as $i => $line) {
            $safeX = max(1, min($x, $this->termW - 9));
            $this->move($row + $i, $safeX);
            echo $this->fg256(81, $line);
        }
    }

    private function drawBar(int $row, int $pct)
    {
        $label = " OCR ";
        $pctStr = str_pad($pct, 3) . "%";
        $suffix = " {$pctStr} ";

        $avail = $this->termW - mb_strlen($label) - mb_strlen($suffix) - 2;
        $avail = max(10, $avail);

        $filled = (int) round($avail * $pct / 100);
        $empty  = $avail - $filled;

        $bar = $this->fg256(82, str_repeat("█", $filled))
             . $this->fg256(238, str_repeat("░", $empty));

        $this->move($row, 1);
        echo $this->fg256(252, $label)
           . $this->fg256(244, "[") . $bar . $this->fg256(244, "]")
           . $this->fg256(82, $suffix);
    }

    private function drawSpinner(int $row)
    {
        $spinner = ['⠋','⠙','⠹','⠸','⠼','⠴','⠦','⠧','⠇','⠏'];
        $s = $spinner[$this->tick % count($spinner)];
        $this->tick++;

        $this->move($row, $this->termW - 4);
        echo $this->fg256(81, $s);
    }

    private function drawStats(int $row, float $elapsed, int $pct)
    {
        $eta = $pct > 0 ? round($elapsed * (100 - $pct) / $pct) : 0;

        $this->move($row, 1);
        echo $this->fg256(240, "  elapsed: ")
           . $this->fg256(246, gmdate("i:s", (int)$elapsed))
           . $this->fg256(240, "   eta: ")
           . $this->fg256(246, gmdate("i:s", $eta));
    }

    private function drawDone(float $elapsed)
    {
        $this->cls();

        $this->move(3, 3);
        echo $this->fg256(46, "✔ OCR COMPLETADO");

        $this->move(5, 1);
        echo "Tiempo total: " . gmdate("i:s", (int)$elapsed);

        $this->move(7, 1);
    }

    // ─────────────────────────────────────
    // ANSI HELPERS
    // ─────────────────────────────────────
    private function cls() { echo "\033[2J\033[H"; }
    private function move($r, $c) { echo "\033[{$r};{$c}H"; }
    private function hide() { echo "\033[?25l"; }
    private function show() { echo "\033[?25h"; }

    private function fg256($n, $s) { return "\033[38;5;{$n}m{$s}\033[0m"; }

    private function termWidth(): int
    {
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

    // ─────────────────────────────────────
    // FRAMES
    // ─────────────────────────────────────
    private const FRAMES = [
        [" /\\_/\\  ", "( o.o ) ", " > ^ <  "],
        [" /\\_/\\  ", "( -.o ) ", " > ^ <  "],
        [" /\\_/\\  ", "( o.o ) ", " > v <  "],
        [" /\\_/\\  ", "( o.- ) ", " > ^ <  "],
        [" /\\_/\\  ", "( ^.^ ) ", " > ^ <  "],
        [" /\\_/\\  ", "( >.< ) ", " > ^ <  "],
    ];
}