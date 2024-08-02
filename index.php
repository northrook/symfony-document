<?php

include( 'vendor/autoload.php' );

$app = new  \Northrook\DevEnv();

$app->document();

$document = new \Northrook\Symfony\Service\Document\DocumentService(
    $app->requestStack,
);

\Northrook\AssetGenerator\Asset::setDirectories(
    $app->projectDir,
    $app->cacheDir,
    "$app->cacheDir/public",
    "$app->cacheDir/public/assets",
);

$document->meta( 'title', $app->title )
         ->asset(
             new \Northrook\Asset\Stylesheet( __DIR__ . '/vendor/northrook/dev-env/test/stylesheet.css' ),
         // new \Northrook\Asset\Script( __FILE__ ),
         );

$document->set(
    title       : "Documente!",
    description : 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
    keywords    : $_SERVER,
    author      : 'John Doe',

);

$parameters = $document->getDocumentParameters();

dump(
    // $document,
    $parameters,
    $parameters->id,
    $parameters->documentMeta(),
    $parameters->assets('stylesheet.css'),

);