<?php

declare(strict_types=1);

namespace App\ValueObjects;

use Carbon\CarbonInterface;
use InvalidArgumentException;

final readonly class Season
{
    /**
     * Month (1-12) at which a new season starts.
     */
    private const int START_MONTH = 7;

    private function __construct(
        public string $label,
        public int $startYear,
        public int $endYear,
    ) {
        if ($endYear !== $startYear + 1) {
            throw new InvalidArgumentException(sprintf('Season end year must be the year after the start year, got [%d-%d].', $startYear, $endYear));
        }
    }

    public static function fromYears(int $startYear): self
    {
        return new self(
            label: sprintf('%d-%d', $startYear, $startYear + 1),
            startYear: $startYear,
            endYear: $startYear + 1,
        );
    }

    public static function fromDate(CarbonInterface $date): self
    {
        return self::fromYears($date->month >= self::START_MONTH ? $date->year : $date->year - 1);
    }

    public static function current(): self
    {
        return self::fromDate(now());
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function fromString(string $label): self
    {
        if (preg_match('/^(\d{4})-(\d{4})$/', $label, $matches) !== 1) {
            throw new InvalidArgumentException(sprintf('Season label must match "YYYY-YYYY", got [%s].', $label));
        }

        $startYear = (int) $matches[1];
        $endYear = (int) $matches[2];

        if ($endYear !== $startYear + 1) {
            throw new InvalidArgumentException(sprintf('Season end year must be the year after the start year, got [%s].', $label));
        }

        return new self($label, $startYear, $endYear);
    }

    public function equals(self $other): bool
    {
        return $this->label === $other->label;
    }

    public function previous(): self
    {
        return self::fromYears($this->startYear - 1);
    }

    public function next(): self
    {
        return self::fromYears($this->startYear + 1);
    }
}
