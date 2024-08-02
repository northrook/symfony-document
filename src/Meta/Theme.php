<?php

declare( strict_types = 1 );

namespace Northrook\Symfony\Service\Document\Meta;

use InvalidArgumentException;
use JetBrains\PhpStorm\ExpectedValues;

// Make this a stand-alone service with deep ties to the Stylesheet Generator

/**
 * @internal
 */
final readonly class Theme implements \Stringable
{
    public const SCHEME = [
        'normal',
        'light',
        'dark',
        'light dark',
        'dark light',
        'only light',
        'only dark',
    ];

    /**
     * @param string   $color   Primary accent color
     * @param string   $scheme  Preferred color scheme
     * @param ?string  $name    Name of the theme
     */
    public function __construct(
        public string  $color,
        #[ExpectedValues( values : self::SCHEME )]
        public string  $scheme = 'dark light',
        public ?string $name = 'system',
    ) {

        // TODO [low] Validate $color formatting; if starts with # is hex must be 1string6int etc

        if ( !in_array( $scheme, Theme::SCHEME ) ) {
            throw new InvalidArgumentException(
                "Scheme must be one of " . print_r( Theme::SCHEME, true ),
            );
        }
    }

    public function __toString() : string {
        return $this->name ?? '';
    }
}