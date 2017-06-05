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
use LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\UriTemplateActionBuilder;
use LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use LINE\LINEBot\MessageBuilder\LocationMessageBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselTemplateBuilder;

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

        $json = $this->searchLocation($keyword, $location);

        if(!isset($json->results[0]->vicinity)){
            $this->bot->replyText( $replyToken, "ไม่เจอจริง" ); //TODO random deny word(s)
            return; 
        }

        if(count($json->results) == 1){
            error_log("There is only one search");
            $latitude = $json->results[0]->geometry->location->lat;
            $longitude = $json->results[0]->geometry->location->lng;
            $title = $json->results[0]->name;
            $address = $json->results[0]->vicinity;
            error_log("There is only one search and get info completed");

            $reff = $json->results[0]->photos[0]->photo_reference;
            error_log("Requesting image");
            $imageUrl = $this->requestImageUrl($reff);

            error_log("request image completed");

            $placeId = $json->results[0]->place_id;
            $detail = $this->requestLocationDetail($placeId);
            error_log("request detail completed");
            error_log("request detail " . json_encode($detail));

            $actionList = [];
            if(isset($detail->result->website)){
                $actionList[] = new UriTemplateActionBuilder('Website', $detail->result->website);
            } else {
                $actionList[] = new MessageTemplateActionBuilder('No Website', 'The restaurant has no website');
            }
            $actionList[] = new UriTemplateActionBuilder('Map', $detail->result->url);
            $buttonTemplateBuilder = new ButtonTemplateBuilder(
            'Single Restaurant Result',
            'Single Restaurant',
            $imageUrl,
            $actionList
            );
            error_log("built button detail completed");

            $templateMessage = new TemplateMessageBuilder('Button alt text', $buttonTemplateBuilder);
            error_log(json_encode($templateMessage->buildMessage()));
            //$this->bot->replyMessage($replyToken, $templateMessage);
        } else { // Show carousel
            error_log("There are more than one search");
            $cap = min(count($json->results),5); //Cap carousel at 5
            $carouselColumns = [];
            for ($i=0; $i < $cap; $i++) {
                $latitude = $json->results[$i]->geometry->location->lat;
                $longitude = $json->results[$i]->geometry->location->lng;
                $title = $json->results[$i]->name;
                $address = $json->results[$i]->vicinity;

                $placeId = $json->results[$i]->place_id;
                $detail = $this->requestLocationDetail($placeId);
                error_log("request detail completed");
                error_log("request detail " . json_encode($detail));

                $reff = $detail->result->photos[0]->photo_reference;
                $imageUrl = $this->requestImageUrl($reff);

                $actionList = [];
                if(isset($detail->result->website)){
                    $actionList[] = new UriTemplateActionBuilder('Website', $detail->result->website);
                } else {
                    $actionList[] = new MessageTemplateActionBuilder('No Website', 'The restaurant has no website');
                }
                $actionList[] = new UriTemplateActionBuilder('Map', $detail->result->url);
                $carouselColumns[] = new CarouselColumnTemplateBuilder($detail->result->name,
                                    isset($detail->result->formatted_phone_number) ? $detail->result->formatted_phone_number : $detail->result->formatted_address,
                                    $imageUrl,
                                    $actionList
                                    );    
            }
            $carouselTemplateBuilder = new CarouselTemplateBuilder($carouselColumns);
            $templateMessage = new TemplateMessageBuilder('Multiple Result', $carouselTemplateBuilder);
            error_log(json_encode($templateMessage->buildMessage()));
            $this->bot->replyMessage($replyToken, $templateMessage);
        }
    }

    private function checkUniqueUser(){
        if($this->locationMessage->isRoomEvent() || $this->locationMessage->isGroupEvent()){
            error_log("wont check cause it's a room/group");
            return false;
        }

        $userId = $this->locationMessage->getUserId();
        error_log("checking user " . $userId);
        if($userId == 'U66902f6ba7f571bbe34e12cfd6cf358a'){
            error_log("matched");
            return true;
        }
        return false;
    }

    private function searchLocation($keyword, $location){
        $url = 'https://maps.googleapis.com/maps/api/place/nearbysearch/json?';
        $url .= "key=" . 'AIzaSyBpHGnZLYSvSlgT8xL6GVUQkN0TGMMBpDQ';
        $url .= "&type=" . 'restaurant';
        $url .= "&radius=" . '2000';
        $url .= "&keyword=" . urlencode($keyword);
        $url .= "&location=" . urlencode($location);

        $response = file_get_contents($url);
        $response = urldecode($response);
        $output = json_decode($response);
        return $output;
    }

    private function requestLocationDetail($placeId){
        $url = 'https://maps.googleapis.com/maps/api/place/details/json?';
        $url .= "key=" . 'AIzaSyBpHGnZLYSvSlgT8xL6GVUQkN0TGMMBpDQ';
        $url .= "&place_id=" . $placeId;

        error_log("request detail");
        error_log("URL is " . $url);

        $response = file_get_contents($url);
        $response = urldecode($response);
        $output = json_decode($response);
        return $output;
    }
    
    private function requestImageUrl($refference){
        $url = 'https://maps.googleapis.com/maps/api/place/photo?';
        $url .= "key=" . 'AIzaSyBpHGnZLYSvSlgT8xL6GVUQkN0TGMMBpDQ';
        $url .= "&maxwidth=" . '1040';
        $url .= "&photoreference=" . $refference;
        
        error_log("URL is " . $url);

        return $url;
    }
}
