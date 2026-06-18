<?php

namespace App\Model;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final readonly class ParsedGuessValue
{
    public function __construct(
        public float $degree,
        public float $distance,
    ) {}
}

final class GuessValueParser
{
    private const float MIN_DEGREE = 0.0;
    private const float MAX_DEGREE = 160.0;
    private const float MIN_DISTANCE = 0.0;
    private const float MAX_DISTANCE = 1.0;

    public static function parse(string $value): ParsedGuessValue
    {
        $value = trim($value);
        if ($value === '') {
            throw new BadRequestHttpException('Guess value is required.');
        }

        $parts = explode(',', $value);
        if (count($parts) !== 2) {
            throw new BadRequestHttpException('Guess value must be "angle,distance".');
        }

        [$anglePart, $distancePart] = array_map(trim(...), $parts);
        if ($anglePart === '' || $distancePart === '') {
            throw new BadRequestHttpException('Guess value must be "angle,distance".');
        }

        $degree = self::parseFloat($anglePart, 'angle');
        $distance = self::parseFloat($distancePart, 'distance');

        self::validateDegree($degree);
        self::validateDistance($distance);

        return new ParsedGuessValue($degree, $distance);
    }

    private static function parseFloat(string $part, string $field): float
    {
        if (!is_numeric($part)) {
            throw new BadRequestHttpException(sprintf('Invalid guess %s.', $field));
        }

        return (float) $part;
    }

    private static function validateDegree(float $degree): void
    {
        if ($degree < self::MIN_DEGREE || $degree > self::MAX_DEGREE) {
            throw new BadRequestHttpException('Guess angle must be between 0 and 160.');
        }
    }

    private static function validateDistance(float $distance): void
    {
        if ($distance < self::MIN_DISTANCE || $distance > self::MAX_DISTANCE) {
            throw new BadRequestHttpException('Guess distance must be between 0 and 1.');
        }
    }
}
