<?php

declare(strict_types=1);

namespace SpyWSamara;

use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\ActionFilter\Csrf;
use Bitrix\Main\Engine\ActionFilter\HttpMethod;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserTable;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\Engine\Contract\Controllerable;

class YoutubeLikes extends \CBitrixComponent implements Controllerable
{

    /**
     * Prepare component parameters for use
     *
     * @param array $arParams Raw parameters array
     *
     * @return array Prepared parameters
     */
    public function onPrepareComponentParams($arParams): array
    {
        $arParams = parent::onPrepareComponentParams($arParams);
        $arParams['YOUTUBE_FIELD'] = (string) $arParams['YOUTUBE_FIELD'];
        $arParams['YOUTUBE_KEY'] = (string) $arParams['YOUTUBE_KEY'];
        $arParams['SEARCH_COUNT'] = (int) $arParams['SEARCH_COUNT'];
        if (0 >= $arParams['SEARCH_COUNT'] || 50 < $arParams['SEARCH_COUNT']) {
            $arParams['SEARCH_COUNT'] = 5;
        }

        return $arParams;
    }

    /**
     * Config for AJAX actions
     *
     * @return array Config
     */
    public function configureActions(): array
    {
        return [
            'search' => [
                'prefilters' => [
                    new Authentication(),
                    new HttpMethod([HttpMethod::METHOD_POST]),
                    new Csrf(),
                ],
                'postfilters' => [],
            ],
            'add' => [
                'prefilters' => [
                    new Authentication(),
                    new HttpMethod([HttpMethod::METHOD_POST]),
                    new Csrf(),
                ],
                'postfilters' => [],
            ],
            'remove' => [
                'prefilters' => [
                    new Authentication(),
                    new HttpMethod([HttpMethod::METHOD_POST]),
                    new Csrf(),
                ],
                'postfilters' => [],
            ],
        ];
    }

    /**
     * Search YouTube video
     *
     * @param string $q Search string
     *
     * @return array Found video list
     * @throws \JsonException
     */
    public function searchAction(string $q): array
    {
        $client = new HttpClient();
        $uri = $this->getApiUri()->addParams(
            [
                'part' => 'id,snippet',
                'q' => $q,
            ]
        );
        $response = $client->get($uri);
        $result = \json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        $user = new \CUser();
        $favorite = $this->getUserFavoriteVideos((int) $user->GetID());

        return \array_map(
            function ($item) use ($favorite) {
                return [
                    'id' => $item['id']['videoId'],
                    'title' => $item['snippet']['title'],
                    'author' => $item['snippet']['channelTitle'],
                    'checked' => \in_array($item['id']['videoId'], $favorite),
                    'f' => $favorite,
                    'u' => (new \CUSer())->GetID(),
                ];
            },
            $result['items']
        );
    }

    /**
     * Add new YouTube video id to user favorites
     *
     * @param string $id YouTube video id
     *
     * @return bool Result of append new video id
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function addAction(string $id): bool
    {
        $user = new \CUser();
        $favorites = $this->getUserFavoriteVideos((int) $user->GetID());
        if (!\in_array($id, $favorites)) {
            $favorites[] = $id;

            return $this->setUserFavoriteVideos(
                (int) $user->GetID(),
                $favorites
            );
        }

        return false;
    }

    /**
     * Remove YouTube cideo id from user favorites
     *
     * @param string $id Video id
     *
     * @return bool Result of removing video id
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function removeAction(string $id): bool
    {
        $user = new \CUser();
        $favorites = $this->getUserFavoriteVideos((int) $user->GetID());
        $index = \array_search($id, $favorites);
        if (false !== $index) {
            unset($favorites[$index]);

            return $this->setUserFavoriteVideos(
                (int) $user->GetID(),
                $favorites
            );
        }

        return false;
    }

    /**
     * Main function for execute component on page
     *
     * @return void
     */
    public function executeComponent()
    {
        if (empty($this->arParams['YOUTUBE_FIELD'])) {
            return \ShowError(Loc::getMessage('YOUTUBE_FIELD_EMPTY'));
        }
        if (empty($this->arParams['YOUTUBE_KEY'])) {
            return \ShowError(Loc::getMessage('YOUTUBE_KEY_EMPTY'));
        }
        $user = new \CUser();
        if (!$user->IsAuthorized()) {
            return \ShowError(Loc::getMessage('USER_AUTH_REQ'));
        }
        if ($this->startResultCache(false)) {
            $this->arResult['SIGNED_PARAMETERS'] = $this->getSignedParameters();
            \CJSCore::Init(['ajax']);
            $this->includeComponentTemplate();
        }
    }

    /**
     * Load favorite users youtube videos
     *
     * @param int $userId Bitrix user id
     *
     * @return array List of users favorite youtube videos
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getUserFavoriteVideos(int $userId): array
    {
        // TODO: make cache?
        $result = UserTable::getList(
            [
                'filter' => ['ID' => $userId],
                'select' => [$this->arParams['YOUTUBE_FIELD']],
            ]
        )->fetchAll()[0];

        if (\is_array($result[$this->arParams['YOUTUBE_FIELD']])) {
            return $result[$this->arParams['YOUTUBE_FIELD']];
        }

        return [];
    }

    /**
     * Setup user favorite videos list
     *
     * @param int $userId Bitrix user id
     * @param array $videoIds Video ids list
     *
     * @return bool Result up update user favorite field
     */
    public function setUserFavoriteVideos(int $userId, array $videoIds): bool
    {
        // TODO: reset cache?
        $user = new \CUser();

        return (bool) $user->update(
            (int) $userId,
            [
                $this->arParams['YOUTUBE_FIELD'] => $videoIds,
            ]
        );
    }

    /**
     * Make YouTube API base uri with access token
     *
     * @return \Bitrix\Main\Web\Uri YouTube API base uri
     */
    public function getApiUri(): Uri
    {
        $uri = new Uri('https://www.googleapis.com/youtube/v3/search');
        $uri->addParams(
            [
                'key' => $this->arParams['YOUTUBE_KEY'],
                'maxResults' => $this->arParams['SEARCH_COUNT'],
            ]
        );

        return $uri;
    }

    /**
     * Get list of params keys for sign
     *
     * @return string[] List of signed params keys to use in AJAX
     */
    protected function listKeysSignedParameters(): array
    {
        return [
            'YOUTUBE_FIELD',
            'YOUTUBE_KEY',
            'SEARCH_COUNT',
        ];
    }

}
