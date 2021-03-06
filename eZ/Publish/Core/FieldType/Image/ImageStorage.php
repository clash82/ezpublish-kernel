<?php
/**
 * File containing the ImageStorage Converter class
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */

namespace eZ\Publish\Core\FieldType\Image;

use eZ\Publish\Core\Base\Exceptions\InvalidArgumentException;
use eZ\Publish\Core\Base\Utils\DeprecationWarnerInterface as DeprecationWarner;
use eZ\Publish\Core\FieldType\GatewayBasedStorage;
use eZ\Publish\Core\IO\IOServiceInterface;
use eZ\Publish\Core\IO\MetadataHandler;
use eZ\Publish\SPI\Persistence\Content\Field;
use eZ\Publish\SPI\Persistence\Content\VersionInfo;

/**
 * Converter for Image field type external storage
 */
class ImageStorage extends GatewayBasedStorage
{
    /** @var \eZ\Publish\Core\IO\IOServiceInterface */
    protected $IOService;

    /** @var \eZ\Publish\Core\FieldType\Image\PathGenerator */
    protected $pathGenerator;

    /** @var \eZ\Publish\Core\IO\MetadataHandler */
    protected $imageSizeMetadataHandler;

    /** @var \eZ\Publish\Core\Base\Utils\DeprecationWarnerInterface */
    private $deprecationWarner;

    /** @var \eZ\Publish\Core\FieldType\Image\AliasCleanerInterface */
    protected $aliasCleaner;

    public function __construct(
        array $gateways,
        IOServiceInterface $IOService,
        PathGenerator $pathGenerator,
        MetadataHandler $imageSizeMetadataHandler,
        DeprecationWarner $deprecationWarner,
        AliasCleanerInterface $aliasCleaner = null
    )
    {
        parent::__construct( $gateways );
        $this->IOService = $IOService;
        $this->pathGenerator = $pathGenerator;
        $this->imageSizeMetadataHandler = $imageSizeMetadataHandler;
        $this->deprecationWarner = $deprecationWarner;
        $this->aliasCleaner = $aliasCleaner;
    }

    public function storeFieldData( VersionInfo $versionInfo, Field $field, array $context )
    {
        $contentMetaData = array(
            'fieldId' => $field->id,
            'versionNo' => $versionInfo->versionNo,
            'languageCode' => $field->languageCode,
        );

        // new image
        if ( isset( $field->value->externalData ) )
        {
            $targetPath = sprintf(
                '%s/%s',
                $this->pathGenerator->getStoragePathForField(
                    $field->id,
                    $versionInfo->versionNo,
                    $field->languageCode
                ),
                $field->value->externalData['fileName']
            );

            if ( isset( $field->value->externalData['id'] ) )
            {
                $binaryFile = $this->IOService->loadBinaryFile( $field->value->externalData['id'] );
            }
            else if ( $this->IOService->exists( $targetPath ) )
            {
                $binaryFile = $this->IOService->loadBinaryFile( $targetPath );
            }
            else if ( isset( $field->value->externalData['inputUri'] ) )
            {
                $localFilePath = $field->value->externalData['inputUri'];
                unset( $field->value->externalData['inputUri'] );

                $binaryFileCreateStruct = $this->IOService->newBinaryCreateStructFromLocalFile( $localFilePath );
                $binaryFileCreateStruct->id = $targetPath;
                $binaryFile = $this->IOService->createBinaryFile( $binaryFileCreateStruct );

                $imageSize = getimagesize( $localFilePath );
                $field->value->externalData['width'] = $imageSize[0];
                $field->value->externalData['height'] = $imageSize[1];
            }
            else
            {
                throw new InvalidArgumentException(
                    "inputUri",
                    "No source image could be obtained from the given external data"
                );
            }

            $field->value->externalData['imageId'] = $versionInfo->contentInfo->id . '-' . $field->id;
            $field->value->externalData['uri'] = $binaryFile->uri;
            $field->value->externalData['id'] = $binaryFile->id;
            $field->value->externalData['mime'] = $this->IOService->getMimeType( $binaryFile->id );

            $field->value->data = array_merge(
                $field->value->externalData,
                $contentMetaData
            );

            $field->value->externalData = null;
        }
        // existing image from another version
        else
        {
            if ( $field->value->data === null )
            {
                // Store empty value only with content meta data
                $field->value->data = $contentMetaData;
                return false;
            }

            $this->IOService->loadBinaryFile( $field->value->data['id'] );

            $field->value->data = array_merge(
                $field->value->data,
                $contentMetaData
            );
            $field->value->externalData = null;
        }

        $this->getGateway( $context )->storeImageReference( $field->value->data['uri'], $field->id );

        // Data has been updated and needs to be stored!
        return true;
    }

    public function getFieldData( VersionInfo $versionInfo, Field $field, array $context )
    {
        if ( $field->value->data !== null )
        {
            $field->value->data['imageId'] = $versionInfo->contentInfo->id . '-' . $field->id;
            $binaryFile = $this->IOService->loadBinaryFile( $field->value->data['id'] );
            $field->value->data['id'] = $binaryFile->id;
            $field->value->data['fileSize'] = $binaryFile->size;
            $field->value->data['uri'] = $binaryFile->uri;
        }
    }

    public function deleteFieldData( VersionInfo $versionInfo, array $fieldIds, array $context )
    {
        /** @var \eZ\Publish\Core\FieldType\Image\ImageStorage\Gateway $gateway */
        $gateway = $this->getGateway( $context );

        $fieldXmls = $gateway->getXmlForImages( $versionInfo->versionNo, $fieldIds );

        foreach ( $fieldXmls as $fieldId => $xml )
        {
            $storedFiles = $gateway->extractFilesFromXml( $xml );
            if ( $storedFiles === null )
            {
                continue;
            }

            if ( $this->aliasCleaner )
            {
                $this->aliasCleaner->removeAliases( $storedFiles['original'] );
            }

            foreach ( $storedFiles as $storedFilePath )
            {
                $gateway->removeImageReferences( $storedFilePath, $versionInfo->versionNo, $fieldId );
                if ( $gateway->countImageReferences( $storedFilePath ) === 0 )
                {
                    $binaryFile = $this->IOService->loadBinaryFileByUri( $storedFilePath );
                    $this->IOService->deleteBinaryFile( $binaryFile );
                }
            }
        }
    }

    public function hasFieldData()
    {
        return true;
    }

    public function getIndexData( VersionInfo $versionInfo, Field $field, array $context )
    {
        // @todo: Correct?
        return null;
    }
}
