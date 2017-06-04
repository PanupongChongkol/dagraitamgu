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
use LINE\LINEBot\ImagemapActionBuilder\AreaBuilder;
use LINE\LINEBot\ImagemapActionBuilder\ImagemapMessageActionBuilder;
use LINE\LINEBot\ImagemapActionBuilder\ImagemapUriActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\UriTemplateActionBuilder;
use LINE\LINEBot\Event\MessageEvent\TextMessage;
use LINE\LINEBot\KitchenSink\EventHandler;
use LINE\LINEBot\KitchenSink\EventHandler\MessageHandler\Util\UrlBuilder;
use LINE\LINEBot\MessageBuilder\Imagemap\BaseSizeBuilder;
use LINE\LINEBot\MessageBuilder\ImagemapMessageBuilder;
use LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ConfirmTemplateBuilder;
use LINE\LINEBot\MessageBuilder\LocationMessageBuilder;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;

class TextMessageHandler implements EventHandler
{
    /** @var LINEBot $bot */
    private $bot;
    /** @var \Monolog\Logger $logger */
    private $logger;
    /** @var \Slim\Http\Request $logger */
    private $req;
    /** @var TextMessage $textMessage */
    private $textMessage;

    const LOCATION_CSV = __DIR__ . '/location.csv';

    /**
     * TextMessageHandler constructor.
     * @param $bot
     * @param $logger
     * @param \Slim\Http\Request $req
     * @param TextMessage $textMessage
     */
    public function __construct($bot, $logger, \Slim\Http\Request $req, TextMessage $textMessage)
    {
        $this->bot = $bot;
        $this->logger = $logger;
        $this->req = $req;
        $this->textMessage = $textMessage;
    }

    public function handle()
    {
        $text = strtolower($this->textMessage->getText());
        $replyToken = $this->textMessage->getReplyToken();
        $this->logger->info("Got text message from $replyToken: $text");
        error_log("Got text message from $replyToken: $text");

        switch ($text) {
            case 'profilee':
                $userId = $this->textMessage->getUserId();
                $this->sendProfile($replyToken, $userId);
                break;
            case 'ลาก่อย':
                if ($this->textMessage->isRoomEvent()) {
                    $this->bot->replyText($replyToken, 'Leaving room');
                    $this->bot->leaveRoom($this->textMessage->getRoomId());
                    break;
                }
                if ($this->textMessage->isGroupEvent()) {
                    $this->bot->replyText($replyToken, 'Leaving group');
                    $this->bot->leaveGroup($this->textMessage->getGroupId());
                    break;
                }
                $this->bot->replyText($replyToken, 'Bot cannot leave from 1:1 chat');
                break;
            case 'confirmm':
                $ConfirmTemplateBuilder = new ConfirmTemplateBuilder(
                    'Do it?', 
                    [
                        new PostbackTemplateActionBuilder('Add to cart', 'action=add&itemid=123'),
                        new MessageTemplateActionBuilder('No', 'No!'),
                    ]
                );
                $templateMessage = new TemplateMessageBuilder('Confirm alt text', $ConfirmTemplateBuilder);
                error_log(json_encode($templateMessage->buildMessage()));
                $this->bot->replyMessage($replyToken, $templateMessage);
                break;
            case 'buttonss':
                $imageUrl = UrlBuilder::buildUrl($this->req, ['static', 'buttons', '1040.jpg']);
                $buttonTemplateBuilder = new ButtonTemplateBuilder(
                    'My button sample',
                    'Hello my button',
                    $imageUrl,
                    [
                        new MessageTemplateActionBuilder('Say message', 'hello 1hello'),
                        new MessageTemplateActionBuilder('Say message', 'hello 2hello'),
                        new MessageTemplateActionBuilder('Say message', 'hello 3hello'),
                        new MessageTemplateActionBuilder('Say message', 'hello 4hello'),
                    ]
                );
                $templateMessage = new TemplateMessageBuilder('Button alt text', $buttonTemplateBuilder);
                error_log(json_encode($templateMessage->buildMessage()));
                $this->bot->replyMessage($replyToken, $templateMessage);
                break;
            case 'carousell':
                $imageUrl = UrlBuilder::buildUrl($this->req, ['static', 'buttons', '1040.jpg']);
                error_log("The url is " . $imageUrl);
                $carouselTemplateBuilder = new CarouselTemplateBuilder([
                    new CarouselColumnTemplateBuilder('foo', 'bar', $imageUrl, [
                        new UriTemplateActionBuilder('Go to line.me', 'https://line.me'),
                        new PostbackTemplateActionBuilder('Buy', 'action=buy&itemid=123'),
                         new MessageTemplateActionBuilder('Say message', 'hello hello'),
                   ]),
                    new CarouselColumnTemplateBuilder('buz', 'qux', $imageUrl, [
                        new PostbackTemplateActionBuilder('Add to cart', 'action=add&itemid=123'),
                        new MessageTemplateActionBuilder('Say message', 'hello hello'),
                          new MessageTemplateActionBuilder('Say message', 'hello hello'),
                  ]),
                ]);
                $templateMessage = new TemplateMessageBuilder('Button alt text', $carouselTemplateBuilder);
                error_log(json_encode($templateMessage->buildMessage()));
                $this->bot->replyMessage($replyToken, $templateMessage);
                break;
            case 'imagemapp':
                $richMessageUrl = UrlBuilder::buildUrl($this->req, ['static', 'rich']);
                error_log("The url is " . $richMessageUrl);
                $imagemapMessageBuilder = new ImagemapMessageBuilder(
                    $richMessageUrl,
                    'This is alt text',
                    new BaseSizeBuilder(1040, 1040),
                    [
                        new ImagemapUriActionBuilder(
                            'https://store.line.me/family/manga/en',
                            new AreaBuilder(0, 0, 520, 520)
                        ),
                        new ImagemapUriActionBuilder(
                            'https://store.line.me/family/music/en',
                            new AreaBuilder(520, 0, 520, 520)
                        ),
                        new ImagemapUriActionBuilder(
                            'https://store.line.me/family/play/en',
                            new AreaBuilder(0, 520, 520, 520)
                        ),
                        new ImagemapMessageActionBuilder(
                            'URANAI!',
                            new AreaBuilder(520, 520, 520, 520)
                        )
                    ]
                );
                error_log(json_encode($imagemapMessageBuilder->buildMessage()));
                $this->bot->replyMessage($replyToken, $imagemapMessageBuilder);
                break;
            default:
                //$this->echoBack($replyToken, $text);
                break;
        }
//AIzaSyBpHGnZLYSvSlgT8xL6GVUQkN0TGMMBpDQ  : google MAP key

        if(strpos($text, 'แดกไร') !== false){
            if($this->checkUniqueUser()){
                $this->bot->replyText(
                    $replyToken,
                    "ไก่ทอด"
                );
                return;
            }

            $this->randomFood($replyToken);
        }
        if(strpos($text, 'แดกไหน') !== false){

            if($this->checkUniqueUser()){
                $this->bot->replyText(
                    $replyToken,
                    "KFC!!"
                );
                return;
            }

            $this->bot->replyText($replyToken, 'อยากไปแดกแถวไหนละ');
        }
        if(strpos($text, 'สุ่ม') !== false){
            if(strpos($text, 'มา') !== false){
                $keyword = str_replace('สุ่ม', '', $text);
                $keyword = str_replace('มา', '', $keyword);
                if($keyword == ''){
                    $keyword = 'restaurant';
                }
                if($this->checkUniqueUser()){
                    $keyword = 'kfc';
                }
                $location = $this->load(self::LOCATION_CSV, 'id'); 
                shuffle($location);
                $ranLocation = array_splice($location, 0)[0];
                $location = $ranLocation['lat'] . ',' . $ranLocation ['lng'];

                $json = $this->searchLocation($keyword, $location);

                if(!isset($json->results[0]->vicinity)){
                    $this->bot->replyText( $replyToken, "ไม่เจอจริง" );
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
        }
    }

     /**
     * @param string $replyToken
     * @param string $text
     */
    private function randomFood($replyToken)
    {

        $meat = ['หมู','ปลา','ไก่','กุ้ง','หอย','ปู','เนื้อ','ผัก','เห็ด'];
        $method = ['ผัด','ต้ม','ทอด'];
        $sauce = ['น้ำมันหอย','พริกไทยดำ','ผงกะหรี่','เปรี้ยวหวาน','กระเทียม'];

        $text = $meat[array_rand($meat)] . $method[array_rand($method)] . $sauce[array_rand($sauce)];
        //$this->logger->info("Returns echo message $replyToken: $text");

        $this->bot->replyText($replyToken, $text);
    }

    /**
     * @param string $replyToken
     * @param string $text
     */
    private function echoBack($replyToken, $text)
    {
        $this->logger->info("Returns echo message $replyToken: $text");
        $this->bot->replyText($replyToken, $text);
    }

    private function sendProfile($replyToken, $userId)
    {
        if (!isset($userId)) {
            $this->bot->replyText($replyToken, "Bot can't use profile API without user ID");
            return;
        }

        $response = $this->bot->getProfile($userId);
        if (!$response->isSucceeded()) {
            $this->bot->replyText($replyToken, $response->getRawBody());
            return;
        }

        $profile = $response->getJSONDecodedBody();
        $this->bot->replyText(
            $replyToken,
            'Display name: ' . $profile['displayName'],
            'Status message: ' . $profile['statusMessage']
        );
    }

    private function load($path, $key_field = null){
        if (!is_file($path)) {
            throw new \Exception('File does not exist ' . $path, \Application::ERR_CODE_COMMON);
        }
   
        $fp = fopen($path, 'r');

        $header_array = fgetcsv($fp);
        $use_key = !is_null($key_field) && in_array($key_field, $header_array);
        $csv = null;
        while ($field_array = fgetcsv($fp)) {
            if ($use_key !== false) {
                $row = array_combine($header_array, $field_array);
                $csv[$row[$key_field]] = $row;
            } else {
                $row = array_combine($header_array, $field_array);
                $csv[] = $row;
            }
        }
        fclose($fp);
        return $csv;
    }

    private function checkUniqueUser(){

        if($this->textMessage->isRoomEvent() || $this->textMessage->isGroupEvent()){
            error_log("wont check cause it's a room/group");
            return false;
        }

        $userId = $this->textMessage->getUserId();
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
        $url .= "&radius=" . '10000';
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
