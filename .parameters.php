<?php

use Bitrix\Main\Localization\Loc;

$groups = $params = [];

$params['CACHE_TIME'] = [
    // one year
    'DEFAULT' => 3600 * 24 * 365,
];

$entitiesList = [];
$iterator = \CUserTypeEntity::GetList(
    [],
    ['ENTITY_ID' => 'USER', 'USER_TYPE_ID' => 'string', 'MULTIPLY' => 'Y']
);
while ($row = $iterator->fetch()) {
    $entitiesList[$row['FIELD_NAME']] = $row['FIELD_NAME'];
}

$params['YOUTUBE_FIELD'] = [
    'NAME' => Loc::getMessage('YOUTUBE_FIELD'),
    'TYPE' => 'LIST',
    'VALUES' => $entitiesList,
];
$params['YOUTUBE_KEY'] = [
    'NAME' => Loc::getMessage('YOUTUBE_KEY'),
];
$params['SEARCH_COUNT'] = [
    'NAME' => Loc::getMessage('SEARCH_COUNT'),
    'DEFAULT' => 5,
];

$arComponentParameters = [
    'GROUPS' => $groups,
    'PARAMETERS' => $params,
];
