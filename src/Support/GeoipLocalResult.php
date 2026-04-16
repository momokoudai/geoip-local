<?php

namespace momokoudai\GeoipLocal\Support;

class GeoipLocalResult
{
    public function __construct(
        public readonly ?string $countryCode,
        public readonly ?string $flagCountryCode,
        public readonly ?string $subdivisionCode,
        public readonly ?string $specialCnRegion,
        public readonly ?float $latitude = null,
        public readonly ?float $longitude = null,
        public readonly ?string $zipCode = null,
        public readonly ?string $isp = null,
        public readonly ?string $organization = null,
        public readonly ?int $asn = null,
        public readonly ?string $asOrganization = null
    ) {
    }

    public function toArray(): array
    {
        return [
            'countryCode' => $this->countryCode,
            'flagCountryCode' => $this->flagCountryCode,
            'subdivisionCode' => $this->subdivisionCode,
            'specialCnRegion' => $this->specialCnRegion,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'zipCode' => $this->zipCode,
            'isp' => $this->isp,
            'organization' => $this->organization,
            'asn' => $this->asn,
            'asOrganization' => $this->asOrganization,
        ];
    }
}

