<?php

$creationDate = time();

//////////
// User //
//////////

$anonymousUser = new eZ\Publish\SPI\Persistence\User(
    array(
        'id' => 10,
        'login' => 'anonymous',
        'email' => 'nospam@ez.no',
        'passwordHash' => '4e6f6184135228ccd45f8233d72a0363',
        'hashAlgorithm' => '2',
    )
);

$anonymousUser = $spi->userHandler()->create( $anonymousUser );

$adminUser = new eZ\Publish\SPI\Persistence\User(
    array(
        'id' => 14,
        'login' => 'admin',
        'email' => 'nospam@ez.no',
        'passwordHash' => 'c78e3b0f3d9244ed8c6d1c29464bdff9',
        'hashAlgorithm' => '2',
    )
);

$adminUser = $spi->userHandler()->create( $adminUser );

///////////////
// Languages //
///////////////

$engUsLanguageCreate = new eZ\Publish\SPI\Persistence\Content\Language\CreateStruct(
    array(
        'languageCode' => 'eng-US',
        'name' => 'English (American)',
        'isEnabled' => true,
    )
);

$engUsLanguage = $spi->contentLanguageHandler()->create( $engUsLanguageCreate );

//////////////
// Sections //
//////////////

$standardSection = $spi->sectionHandler()->create( 'Standard', 'standard' );

$usersSection = $spi->sectionHandler()->create( 'Users', 'users' );

/////////////////////////
// Content Type Groups //
/////////////////////////

$contentContentTypeGroupCreate = new eZ\Publish\SPI\Persistence\Content\Type\Group\CreateStruct(
    array(
        'name' => array(),
        'description' => array(),
        'identifier' => 'Content',
        'created' => $creationDate,
        'modified' => $creationDate,
        'creatorId' => $adminUser->id,
        'modifierId' => $adminUser->id,
    )
);

$contentContentTypeGroup = $spi->contentTypeHandler()->createGroup( $contentContentTypeGroupCreate );

$usersContentTypeGroupCreate = new eZ\Publish\SPI\Persistence\Content\Type\Group\CreateStruct(
    array(
        'name' => array(),
        'description' => array(),
        'identifier' => 'Users',
        'created' => $creationDate,
        'modified' => $creationDate,
        'creatorId' => $adminUser->id,
        'modifierId' => $adminUser->id,
    )
);

$usersContentTypeGroup = $spi->contentTypeHandler()->createGroup( $usersContentTypeGroupCreate );

///////////////////
// Content Types //
///////////////////

$userGroupTypeCreate = new eZ\Publish\SPI\Persistence\Content\Type\CreateStruct(
    array(
        'name' => array(
            'eng-US' => 'User group',
        ),
        'status' => 0,
        'description' => array(),
        'identifier' => 'user_group',

        'created' => $creationDate,
        'modified' => $creationDate,
        'creatorId' => $adminUser->id,
        'modifierId' => $adminUser->id,

        'remoteId' => '25b4268cdcd01921b808a0d854b877ef',

        'urlAliasSchema' => '',
        'nameSchema' => '<name>',
        'isContainer' => true,
        'initialLanguageId' => 2,

        'sortField' => 1,
        'sortOrder' => 1,

        'groupIds' => array( $usersContentTypeGroup->id ),

        'fieldDefinitions' => array(
            new eZ\Publish\SPI\Persistence\Content\Type\FieldDefinition(
                array(
                    'name' => array(
                        'eng-US' => 'Name',
                    ),
                    'description' => array(),
                    'identifier' => 'name',
                    'fieldGroup' => '',
                    'position' => 1,
                    'fieldType' => 'ezstring',
                    'isTranslatable' => true,
                    'isRequired' => true,
                    'isInfoCollector' => false,
                    'fieldTypeConstraints' => new eZ\Publish\SPI\Persistence\Content\FieldTypeConstraints(
                        array(
                            'validators' => array(
                                'StringLengthValidator' => array(
                                    'maxStringLength' => 255,
                                    'minStringLength' => 0,
                                ),
                            ),
                            'fieldSettings' => NULL,
                        )
                    ),
                    'defaultValue' => new eZ\Publish\SPI\Persistence\Content\FieldValue(
                        array(
                            'data' => NULL,
                            'externalData' => NULL,
                            'sortKey' => NULL,
                        )
                    ),
                    'isSearchable' => true,
                )
            ),
            new eZ\Publish\SPI\Persistence\Content\Type\FieldDefinition(
                array(
                    'name' => array(
                        'eng-US' => 'Description',
                    ),
                    'description' => array(),
                    'identifier' => 'description',
                    'fieldGroup' => '',
                    'position' => 2,
                    'fieldType' => 'ezstring',
                    'isTranslatable' => true,
                    'isRequired' => false,
                    'isInfoCollector' => false,
                    'fieldTypeConstraints' => new eZ\Publish\SPI\Persistence\Content\FieldTypeConstraints(
                        array(
                            'validators' => array(
                                'StringLengthValidator' => array(
                                    'maxStringLength' => 255,
                                    'minStringLength' => 0,
                                ),
                            ),
                            'fieldSettings' => NULL,
                        )
                    ),
                    'defaultValue' => new eZ\Publish\SPI\Persistence\Content\FieldValue(
                        array(
                            'data' => NULL,
                            'externalData' => NULL,
                            'sortKey' => NULL,
                        )
                    ),
                    'isSearchable' => true,
                )
            ),
        ),
        'defaultAlwaysAvailable' => true,
    )
);

$userGroupType = $spi->contentTypeHandler()->create( $userGroupTypeCreate );

$landingPageTypeCreate = new eZ\Publish\SPI\Persistence\Content\Type\CreateStruct(
    array(
        'name' => array(
            'eng-US' => 'Landing Page',
        ),
        'status' => 0,
        'description' => array(),
        'identifier' => 'landing_page',

        'created' => $creationDate,
        'modified' => $creationDate,
        'creatorId' => $adminUser->id,
        'modifierId' => $adminUser->id,

        'remoteId' => 'e36c458e3e4a81298a0945f53a2c81f4',

        'urlAliasSchema' => '',
        'nameSchema' => '<name>',
        'isContainer' => true,
        'initialLanguageId' => 2,

        'sortField' => 1,
        'sortOrder' => 1,

        'groupIds' => array( $contentContentTypeGroup->id ),

        'fieldDefinitions' => array(
            new eZ\Publish\SPI\Persistence\Content\Type\FieldDefinition(
                array(
                    'name' => array(
                        'eng-US' => 'Name',
                    ),
                    'description' => array(
                        'eng-US' => '',
                    ),
                    'identifier' => 'name',
                    'fieldGroup' => '',
                    'position' => 1,
                    'fieldType' => 'ezstring',
                    'isTranslatable' => true,
                    'isRequired' => true,
                    'isInfoCollector' => false,
                    'fieldTypeConstraints' => new eZ\Publish\SPI\Persistence\Content\FieldTypeConstraints(
                        array(
                            'validators' => array(
                                'StringLengthValidator' => array(
                                    'maxStringLength' => 0,
                                    'minStringLength' => 0,
                                ),
                            ),
                            'fieldSettings' => NULL,
                        )
                    ),
                    'defaultValue' => new eZ\Publish\SPI\Persistence\Content\FieldValue(
                        array(
                            'data' => NULL,
                            'externalData' => NULL,
                            'sortKey' => NULL,
                        )
                    ),
                    'isSearchable' => true,
                )
            ),
            new eZ\Publish\SPI\Persistence\Content\Type\FieldDefinition(
                array(
                    'name' => array(
                        'eng-US' => 'Layout',
                    ),
                    'description' => array(
                        'eng-US' => '',
                    ),
                    'identifier' => 'page',
                    'fieldGroup' => '',
                    'position' => 2,
                    'fieldType' => 'ezpage',
                    'isTranslatable' => true,
                    'isRequired' => false,
                    'isInfoCollector' => false,
                    'fieldTypeConstraints' => new eZ\Publish\SPI\Persistence\Content\FieldTypeConstraints(
                        array(
                            'validators' => NULL,
                            'fieldSettings' => new eZ\Publish\Core\FieldType\FieldSettings(
                                array(
                                    'defaultLayout' => '',
                                )
                            )
                        )
                    ),
                    'defaultValue' => new eZ\Publish\SPI\Persistence\Content\FieldValue(
                        array(
                            'data' => NULL,
                            'externalData' => NULL,
                            'sortKey' => NULL,
                        )
                    ),
                    'isSearchable' => false,
                )
            )
        ),
        'defaultAlwaysAvailable' => false,
    )
);

$landingPageType = $spi->contentTypeHandler()->create( $landingPageTypeCreate );

/////////////
// Content //
/////////////

// Location: /

$rootLocationCreate = new eZ\Publish\SPI\Persistence\Content\Location\CreateStruct(
    array(
        'remoteId' => '629709ba256fe317c3ddcee35453a96a',
        'pathIdentificationString' => '',
        'mainLocationId' => '1',
        'sortField' => 1,
        'sortOrder' => 1,
    )
);

$rootLocation = $spi->locationHandler()->create( $rootLocationCreate );

// Location: /users/

$usersLocationCreate = new eZ\Publish\SPI\Persistence\Content\Location\CreateStruct(
    array(
        'priority' => 0,
        'remoteId' => '3f6d92f8044aed134f32153517850f5a',
        'parentId' => $rootLocation->id,
        'pathIdentificationString' => 'users',
        'sortField' => 1,
        'sortOrder' => 1,
    )
);

// Content for /users/

$usersContentCreate = new eZ\Publish\SPI\Persistence\Content\CreateStruct(
    array(
        'name' => 'Users',
        'typeId' => $userGroupType->id,
        'sectionId' => $usersSection->id,
        'ownerId' => $adminUser->id,
        'modified' => $creationDate,

        'locations' => array( $usersLocationCreate ),

        'fields' => array(
            new eZ\Publish\SPI\Persistence\Content\Field(
                array(
                    'fieldDefinitionId' => getFieldDefinition( $userGroupType, 1 ),
                    'type' => 'ezstring',
                    'value' => new eZ\Publish\SPI\Persistence\Content\FieldValue(
                        array(
                            'data' => 'Main group',
                            'externalData' => NULL,
                            'sortKey' => '',
                        )
                    ),
                    'languageCode' => 'eng-US',
                )
            ),
            new eZ\Publish\SPI\Persistence\Content\Field(
                array(
                    'fieldDefinitionId' => getFieldDefinition( $userGroupType, 2 ),
                    'type' => 'ezstring',
                    'value' => new eZ\Publish\SPI\Persistence\Content\FieldValue(
                        array(
                            'data' => 'Users',
                            'externalData' => NULL,
                            'sortKey' => '',
                        )
                    ),
                    'languageCode' => 'eng-US',
                )
            ),
        ),

        'alwaysAvailable' => true,
        'remoteId' => 'f5c88a2209584891056f987fd965b0ba',

        'initialLanguageId' => $engUsLanguage->id,

        'name' => array(
            'eng-US' => 'Users',
        ),
    )
);

$spi->contentHandler()->create( $usersContentCreate );

// Home location:

$homeLocation = new eZ\Publish\SPI\Persistence\Content\Location\CreateStruct(
    array(
        'priority' => '0',
        'remoteId' => 'f3e90596361e31d496d4026eb624c983',
        'parentId' => $rootLocation->id,
        'pathIdentificationString' => '',
        'sortField' => 8,
        'sortOrder' => 1,
    )
);

$homeContentCreate = new eZ\Publish\SPI\Persistence\Content\CreateStruct(
    array(
        'name' => 'Home',
        'typeId' => $landingPageType->id,
        'sectionId' => $standardSection->id,
        'ownerId' => $adminUser->id,
        'modified' => $creationDate,

        'locations' => array( $homeLocation ),

        'alwaysAvailable' => 1,
        'remoteId' => '8a9c9c761004866fb458d89910f52bee',

        'initialLanguageId' => $engUsLanguage->id,
        'name' => array(
            'eng-US' => 'Home',
        ),
        'fields' => array(
            new eZ\Publish\SPI\Persistence\Content\Field(
                array(
                    'fieldDefinitionId' => getFieldDefinition( $landingPageType, 1 ),
                    'type' => 'ezstring',
                    'value' => new eZ\Publish\SPI\Persistence\Content\FieldValue(
                        array(
                            'data' => 'Home',
                            'externalData' => NULL,
                            'sortKey' => 'home',
                        )
                    ),
                    'languageCode' => 'eng-US',
                )
            ),
            new eZ\Publish\SPI\Persistence\Content\Field(
                array(
                    'fieldDefinitionId' => getFieldDefinition( $landingPageType, 2 ),
                    'type' => 'ezpage',
                    'value' => new eZ\Publish\SPI\Persistence\Content\FieldValue(
                        array(
                            'data' => new eZ\Publish\Core\FieldType\Page\Parts\Page(
                                new eZ\Publish\Core\FieldType\Page\Service()
                            ),
                            'externalData' => NULL,
                            'sortKey' => NULL,
                        )
                    ),
                    'languageCode' => 'eng-US',
                )
            ),
        ),
    )
);

$spi->contentHandler()->create( $homeContentCreate );

function getFieldDefinition( $type, $position )
{
    foreach( $type->fieldDefinitions as $fieldDefinition )
    {
        if ( $fieldDefinition->position == $position )
        {
            return $fieldDefinition;
        }
    }
    throw new \RuntimeException( "Field definition with position $position not found." );
}
