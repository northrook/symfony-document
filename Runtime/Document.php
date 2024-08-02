<?php

namespace Northrook\Runtime;

// The Variable presented to Latte

use Northrook\Asset\Script;
use Northrook\Asset\Stylesheet;
use Northrook\Core\Trait\PropertyAccessor;
use Northrook\HTML\Element\Attributes;
use Stringable;
use function Northrook\normalizeKey;

/**
 * @property-read string  $id
 * @property-read ?string $title
 * @property-read ?string $description
 * @property-read ?string $keywords
 * @property-read ?string $author
 */
final  class Document
{
    use PropertyAccessor;

    private ?string $id;
    private ?string $title;
    private array   $sent   = [];
    private array   $meta   = [];
    private array   $assets = [];

    public function __construct(
        private readonly array $bodyAttributes,
        array                  $meta = [],
        array                  $assets = [],

    ) {
        $this->id    = $meta[ 'document.id' ] ?? null;
        $this->title = $meta[ 'document.title' ] ?? null;
        unset( $meta[ 'document.id' ], $meta[ 'document.title' ] );
        $this->meta   = $meta;
        $this->assets = $assets;
    }

    public function __get( string $property ) {
        return match ( $property ) {
            'id'                                => $this->id(),
            'title'                             => $this->title(),
            'description', 'keywords', 'author' => $this->getDocument( "$property" ),
            default                             => null,
        };
    }

    public function id( ?string $set = null ) : string {
        return normalizeKey( $this->id ?? $set ?? 'top' );
    }

    public function title( ?string $set = null ) : string {
        if ( $set || !$this->title ) {
            return $this->title = normalizeKey( $set ?? $_SERVER[ 'HTTP_HOST' ] ?? '' );
        }
        return $this->title;
    }

    /**
     * @param string|Stringable  ...$set
     *
     * @return array
     */
    public function bodyAttributes( string | Stringable ...$set ) : array {
        return \array_merge( $this->bodyAttributes, $set );
    }

    public function documentMeta() : array {
        $meta = [];

        foreach ( $this->meta as $name => $value ) {
            if ( !\str_starts_with( $name, 'document' ) || \in_array( $name, $this->sent ) ) {
                continue;
            }
            $meta[ $this->getMetaName( $name ) ] = $value;
        }

        return $meta;
    }

    public function assets( string $get = 'all' ) : array {

        $assets = [];

        if ( $get === 'all' ) {
            foreach ( $this->assets as $type ) {
                foreach ( $type as $asset ) {
                    $assets[] = (string ) $asset;
                }
            }
            return $assets;
        }

        if ( \in_array( $get, [ 'style', 'styles', 'stylesheet', 'css' ] ) ) {
            $get = 'stylesheet';
        }
        elseif ( \in_array( $get, [ 'script', 'scripts', 'js' ] ) ) {
            $get = 'script';
        }
        else {
            return [];
        }

        if ( \array_key_exists( $get, $this->assets ) ) {
            foreach ( $this->assets[ $get ] as $asset ) {
                $assets[] = (string ) $asset;
            }
        }

        return $assets;
    }

    public function metaTags() : array {

        $meta = [];

        foreach ( $this->meta as $name => $value ) {
            if ( \in_array( $name, $this->sent ) ) {
                continue;
            }
            $meta[][ $this->getMetaName( $name ) ] = $value;
        }

        return $meta;
    }

    private function getDocument( string $meta ) : string {
        $meta         = "document.$meta";
        $this->sent[] = $meta;
        return $this->meta[ $meta ] ?? '';
    }

    private function getMetaName( string $key ) : string {
        if ( !\str_contains( $key, '.' ) ) {
            return $key;
        }

        return \substr( $key, \strrpos( $key, '.' ) + 1 );
    }


}