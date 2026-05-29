<?php
declare(strict_types=1);

class PasswordGenerator
{
    private const LOWERCASE = 'abcdefghijklmnopqrstuvwxyz';
    private const UPPERCASE = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private const DIGITS    = '0123456789';
    private const SPECIALS  = '!@#$%^&*()-_=+[]{}|;:,.<>?';

    public function generate(
        int  $length,
        int  $lower    = 0,
        int  $upper    = 0,
        int  $specials = 0,
        int  $digits   = 0,
        bool $usePercent = false
    ): string {
        if ($length < 4) {
            throw new InvalidArgumentException('Length must be at least 4.');
        }

        if ($usePercent) {
            $lower    = (int) round($length * $lower    / 100);
            $upper    = (int) round($length * $upper    / 100);
            $specials = (int) round($length * $specials / 100);
            $digits   = (int) round($length * $digits   / 100);
        }

        if ($lower === 0 && $upper === 0 && $specials === 0 && $digits === 0) {
            $lower  = (int) ceil($length / 4);
            $upper  = (int) ceil($length / 4);
            $digits = (int) ceil($length / 4);
            $specials = $length - $lower - $upper - $digits;
        }

        $total = $lower + $upper + $specials + $digits;
        if ($total > $length) {
            throw new InvalidArgumentException(
                "Sum of character-type counts ({$total}) exceeds total length ({$length})."
            );
        }

        $extra = $length - $total;
        $lower += $extra;

        $chars  = $this->pick(self::LOWERCASE, $lower);
        $chars .= $this->pick(self::UPPERCASE, $upper);
        $chars .= $this->pick(self::SPECIALS,  $specials);
        $chars .= $this->pick(self::DIGITS,    $digits);

        return $this->shuffle($chars);
    }

    private function pick(string $charset, int $count): string
    {
        $max    = strlen($charset) - 1;
        $result = '';
        for ($i = 0; $i < $count; $i++) {
            $result .= $charset[random_int(0, $max)];
        }
        return $result;
    }

    private function shuffle(string $str): string
    {
        $arr = str_split($str);
        $n   = count($arr);
        for ($i = $n - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$arr[$i], $arr[$j]] = [$arr[$j], $arr[$i]];
        }
        return implode('', $arr);
    }
}
