<?php

declare( strict_types = 1 );

namespace Northrook\Symfony\Service\Document;

use JetBrains\PhpStorm\ExpectedValues;
use Northrook\Asset\Script;
use Northrook\Asset\Stylesheet;
use Northrook\Asset\Type\Asset;
use Northrook\Asset\Type\InlineAsset;
use Northrook\AssetManager;
use Northrook\Runtime;
use Northrook\Symfony\Service\Document\Meta\Theme;
use Psr\Log\LoggerInterface;
use Stringable;
use Northrook\HTML\Element\Attributes;
use Symfony\Component\Cache\Adapter\TraceableTagAwareAdapter;
use Symfony\Component\HttpFoundation as Http;
use Symfony\Component\HttpFoundation\Request;
use function Northrook\normalizeKey;
use function Northrook\toString;

final class DocumentService
{
    private const META_GROUPS = [
        'document' => [ 'title', 'description', 'author', 'keywords' ],
        'theme'    => [ 'color', 'scheme', 'name' ],
    ];

    private array $document = [
        'id'          => 'top',
        'title'       => null,
        'description' => null,
        'keywords'    => null,
        'author'      => null,
    ];

    private array $body   = [];
    private array $meta   = [];
    private array $robots = [];
    private array $assets = [];

    /** @var bool Determines how robot tags will be set */
    public bool $isPublic = false;


    public function __construct(
        public readonly ?AssetManager      $asset,
        private readonly Http\RequestStack $requestStack,
        private readonly ?LoggerInterface  $logger = null,
    ) {}

    public function set(
        ?string               $title = null,
        ?string               $description = null,
        null | string | array $keywords = null,
        ?string               $author = null,
        ?string               $id = null,
    ) : DocumentService {
        $set = \array_filter( \get_defined_vars() );

        foreach ( $set as $name => $value ) {
            $this->document[ $name ] = toString( $value, ', ' );
        }

        return $this;
    }

    /**
     * Set an arbitrary meta tag.
     *
     * - This method does not validate the name or content.
     * - The name is automatically prefixed with the group if relevant.
     *
     * @param string  $name  = ['title', 'description', 'author', 'keywords'][$any]
     * @param string  $content
     *
     * @return $this
     */
    public function meta( string $name, string $content ) : DocumentService {
        $group = $this->metaGroup( $name );

        if ( $group && \property_exists( $this, $group ) ) {
            ( $this->$group )[ $name ] = $content;
        }
        else {
            $this->meta[ $name ] = $content;
        }

        return $this;
    }

    public function body( ...$set ) : self {
        $this->body = \array_merge( $this->body, $set );
        return $this;
    }

    public function asset( Script | Stylesheet ...$enqueue ) : DocumentService {
        foreach ( $enqueue as $asset ) {
            $this->assets[ $asset->type ][] = $asset;
        }
        return $this;
    }

    /**
     * @param string  $href
     * @param array   $attributes
     *
     * @return $this
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/link MDN
     */
    public function link(
        string $href,
        array  $attributes = [],
    ) : DocumentService {
        $this->assets[ 'link' ][] = [ 'href' => $href ] + $attributes;
        return $this;
    }

    /**
     * @param string  $bot      = [ 'googlebot', 'bingbot', 'yandexbot'][$any]
     * @param string  ...$rule  = [
     *                          'index', 'noindex', 'follow', 'nofollow',
     *                          'index, follow', 'noindex, nofollow',
     *                          'noarchive', 'nosnippet', 'nositelinkssearchbox'
     *                          ][$any]
     *
     * @return DocumentService
     *
     * @link https://developers.google.com/search/docs/crawling-indexing/robots-meta-tag Documentation
     */
    public function robots( string $bot, string ...$rule ) : DocumentService {

        $rules = [];

        foreach ( $rule as $content ) {
            if ( !\is_string( $content ) ) {
                $this->logger->error(
                    'Invalid robots rule for {bot}, a string is required, but {type} was provided.',
                    [ 'bot' => $bot, 'type' => \gettype( $content ) ],
                );
                continue;
            }

            if ( \str_contains( $content, ',' ) ) {
                foreach ( \explode( ',', $content ) as $value ) {
                    $rules[] = \trim( $value );
                }
            }
            else {
                $rules[] = \trim( $content );
            }
        }

        $this->robots[ $bot ] = $rules;

        return $this;
    }

    public function theme(
        string  $color,
        #[ExpectedValues( values : Theme::SCHEME )]
        string  $scheme = 'dark light',
        ?string $name = 'system',
    ) : DocumentService {

        // Needs to generate theme.scheme.color,
        // this is to allow for different colors based on light/dark

        foreach ( [
            'color'  => $color,
            'scheme' => $scheme,
            'name'   => $name,
        ] as $metaName => $content ) {
            $this->meta( "theme.$metaName", $content );
        }
        return $this;
    }


    public function getDocumentParameters() : Runtime\Document {

        $currentPath = $this->request()?->getPathInfo();

        if ( $currentPath && !\array_key_exists( 'id', $this->body ) ) {
            $this->body[ 'id' ] = $currentPath === '/' ? 'index' : $currentPath;
        }

        // Force nofollow if the Document isn't explicitly marked as public
        if ( false === $this->isPublic ) {
            $this->robots = [ 'robots' => 'noindex, nofollow' ];
            $this->request()->headers->set( 'X-Robots-Tag', 'noindex, nofollow' );
        }

        foreach ( $this->document as $meta => $value ) {
            $this->meta[ "document.$meta" ] = $value;
        }

        $this->meta += $this->robots;

        $assets = [
            'stylesheet' => $this->asset->getEnqueued( 'stylesheet' ),
            'script'     => $this->asset->getEnqueued( 'script' ),
        ];

        dump( $this);

        $this->assets = array_merge( $this->assets, $assets );

        return new Runtime\Document(
            $this->body,
            $this->meta,
            $this->assets,
        );
    }

    private function request() : ?Request {
        return $this->requestStack->getCurrentRequest();
    }

    private function metaGroup( string $name ) : false | string {
        foreach ( DocumentService::META_GROUPS as $group => $names ) {
            if ( \in_array( $name, $names ) ) {
                return $group;
            }
        }
        return false;
    }

}