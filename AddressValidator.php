<?php
declare(strict_types=1);

class AddressValidator
{
    public static function validate(AddressDto $dto): void
    {
        if (!trim($dto->address ?? '')
            || !trim($dto->city ?? '')
            || !trim($dto->country ?? '')
            || !trim($dto->state ?? '')
        ) {
            throw new InvalidArgumentException('Missing required search parameters: address, city, country, state');
        }

        if (($dto->country ?? '') !== 'US') {
            throw new InvalidArgumentException('Works for US only');
        }
    }
}
