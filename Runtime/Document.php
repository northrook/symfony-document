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
 * @property-read ?string $theme
 *
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

    public function __get( string $property ) : ?string {
        $value = match ( $property ) {
            'id'                                => $this->id(),
            'title'                             => $this->title(),
            'description', 'keywords', 'author' => $this->getDocument( "$property" ),
            'theme'                             => $this->meta[ 'theme.name' ] ?? null,
            default                             => null,
        };
        // dump( "$property =>", $value );

        return $value;
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
å
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

    public function meta( ?string $get = null ) : array {

        $tags = [];

        foreach ( $this->meta as $name => $value ) {
            if ( !$value
                 || ( $get && !\str_starts_with( $name, $get ) )
                 || \in_array( $name, $this->sent ) ) {
                continue;
            }
            $meta = [
                'name'    => $this->getMetaName( $name ),
                'content' => $value,
            ];

            $this->sent[] = $name;
            $tags[] = $meta;
        }

        return $tags;
    }

    private function getDocument( string $meta ) : ?string {
        $meta         = "document.$meta";
        $this->sent[] = $meta;
        return $this->meta[ $meta ] ?? null;
    }

    private function getMetaName( string $key ) : string {
        if ( !\str_contains( $key, '.' ) ) {
            return $key;
        }

        return \substr( $key, \strrpos( $key, '.' ) + 1 );
    }


}