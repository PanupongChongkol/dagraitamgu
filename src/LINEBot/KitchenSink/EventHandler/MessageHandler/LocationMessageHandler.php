<?php

/**
 * Copyright 2016 LINE Corporation
 *
 * LINE Corporation licenses this file to you under the Apache License,
 * version 2.0 (the "License"); you may not use this file except in compliance
 * with the License. You may obtain a copy of the License at:
 *
 *   https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */

namespace LINE\LINEBot\KitchenSink\EventHandler\MessageHandler;

use LINE\LINEBot;
use LINE\LINEBot\Event\MessageEvent\LocationMessage;
use LINE\LINEBot\KitchenSink\EventHandler;
use LINE\LINEBot\MessageBuilder\LocationMessageBuilder;

class LocationMessageHandler implements EventHandler
{
    /** @var LINEBot $bot */
    private $bot;
    /** @var \Monolog\Logger $logger */
    private $logger;
    /** @var LocationMessage $event */
    private $locationMessage;

    /**
     * LocationMessageHandler constructor.
     * @param LINEBot $bot
     * @param \Monolog\Logger $logger
     * @param LocationMessage $locationMessage
     */
    public function __construct($bot, $logger, LocationMessage $locationMessage)
    {
        $this->bot = $bot;
        $this->logger = $logger;
        $this->locationMessage = $locationMessage;
    }

    public function handle()
    {
        $replyToken = $this->locationMessage->getReplyToken();
        $latitude = $this->locationMessage->getLatitude();
        $longitude = $this->locationMessage->getLongitude();

        $keyword = 'restaurant';

        if($this->checkUniqueUser()){
            $keyword = 'kfc';
        }

        $location = $latitude . ',' . $longitude;

        $url = 'https://maps.googleapis.com/maps/api/place/nearbysearch/json?';
        $url .= "key=" . 'AIzaSyBpHGnZLYSvSlgT8xL6GVUQkN0TGMMBpDQ';
        $url .= "&type=" . 'restaurant';
        $url .= "&radius=" . '10000';
        $url .= "&keyword=" . urlencode($keyword);
        $url .= "&location=" . urlencode($location);

        $response = file_get_contents($url);
        $response = urldecode($response);

        $json = json_decode($response);

        $latitude = $json->results[0]->geometry->location->lat;
        $longitude = $json->results[0]->geometry->location->lng;
        $title = $json->results[0]->name;
        $address = $json->results[0]->vicinity;
        
        error_log("Replying is " . $title . " at " . $address);

        $this->bot->replyMessage(
            $replyToken,
            new LocationMessageBuilder($title, $address, $latitude, $longitude)
        );
    }

    private function checkUniqueUser(){
        /*if($this->locationMessage->isRoomEvent() || $this->locationMessage->isGroupEvent()){
            error_log("wont check cause it's a room/group");
            return false;
        }*/

        $userId = $this->locationMessage->getUserId();
        error_log("checking user " . $userId);
        if($userId == 'U66902f6ba7f571bbe34e12cfd6cf358a'){
            error_log("matched");
            return true;
        }
        return false;
    }
}
