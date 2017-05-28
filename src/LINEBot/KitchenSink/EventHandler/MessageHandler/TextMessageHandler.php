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
            case 'profile':
                $userId = $this->textMessage->getUserId();
                $this->sendProfile($replyToken, $userId);
                break;
            case 'bye':
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
            case 'confirm':
                $this->bot->replyMessage(
                    $replyToken,
                    new TemplateMessageBuilder(
                        'Confirm alt text',
                        new ConfirmTemplateBuilder('Do it?', [
                            new MessageTemplateActionBuilder('Yes', 'Yes!'),
                            new MessageTemplateActionBuilder('No', 'No!'),
                        ])
                    )
                );
                break;
            case 'buttons':
                $imageUrl = UrlBuilder::buildUrl($this->req, ['static', 'buttons', '1040.jpg']);
                $buttonTemplateBuilder = new ButtonTemplateBuilder(
                    'My button sample',
                    'Hello my button',
                    $imageUrl,
                    [
                        new UriTemplateActionBuilder('Go to line.me', 'https://line.me'),
                        new PostbackTemplateActionBuilder('Buy', 'action=buy&itemid=123'),
                        new PostbackTemplateActionBuilder('Add to cart', 'action=add&itemid=123'),
                        new MessageTemplateActionBuilder('Say message', 'hello hello'),
                    ]
                );
                $templateMessage = new TemplateMessageBuilder('Button alt text', $buttonTemplateBuilder);
                $this->bot->replyMessage($replyToken, $templateMessage);
                break;
            case 'carousel':
                $imageUrl = UrlBuilder::buildUrl($this->req, ['static', 'buttons', '1040.jpg']);
                $carouselTemplateBuilder = new CarouselTemplateBuilder([
                    new CarouselColumnTemplateBuilder('foo', 'bar', $imageUrl, [
                        new UriTemplateActionBuilder('Go to line.me', 'https://line.me'),
                        new PostbackTemplateActionBuilder('Buy', 'action=buy&itemid=123'),
                    ]),
                    new CarouselColumnTemplateBuilder('buz', 'qux', $imageUrl, [
                        new PostbackTemplateActionBuilder('Add to cart', 'action=add&itemid=123'),
                        new MessageTemplateActionBuilder('Say message', 'hello hello'),
                    ]),
                ]);
                $templateMessage = new TemplateMessageBuilder('Button alt text', $carouselTemplateBuilder);
                $this->bot->replyMessage($replyToken, $templateMessage);
                break;
            case 'imagemap':
                $richMessageUrl = UrlBuilder::buildUrl($this->req, ['static', 'rich']);
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
                $this->bot->replyMessage($replyToken, $imagemapMessageBuilder);
                break;
            default:
                //$this->echoBack($replyToken, $text);
                break;
        }
//AIzaSyBpHGnZLYSvSlgT8xL6GVUQkN0TGMMBpDQ  : google MAP key

        if(strpos($text, 'แดกไร') !== false){
            $this->randomFood($replyToken);
        }
        if(strpos($text, 'แดกไหน') !== false){
            $this->bot->replyText($replyToken, 'อยากไปแดกแถวไหนละ', 'แชร์โลมาด้ายนะ');
        }
        if(strpos($text, 'สุ่ม') !== false){
            if(strpos($text, 'มา') !== false){
                $keyword = str_replace('สุ่ม', '', $text);
                $keyword = str_replace('มา', '', $keyword);
                if($keyword == ''){
                    $keyword = 'random';
                }
                error_log('Keyword will be ' . $keyword);
                error_log("Ramdoming location");
                error_log("will try to open file from " . __DIR__ . '/./location.csv');
                $location = load(__DIR__ . '/./location.csv', 'id'); 
                 error_log("open completed");
                $ranLocation = array_splice($location, 0)[0];
                $location = $ranLocation['lat'] . ',' . $ranLocation ['lng'];
                error_log("at " . $location);

                $url = 'https://maps.googleapis.com/maps/api/place/nearbysearch/json?';
                $url .= "key=" . 'AIzaSyBpHGnZLYSvSlgT8xL6GVUQkN0TGMMBpDQ';
                $url .= "&type=" . 'restaurant';
                $url .= "&radius=" . '1000';
                $url .= "&keyword=" . urlencode($keyword);
                $url .= "&location=" . urlencode($location);

                error_log("Request Url is " . $url);

                $response = file_get_contents($url);
                $response = urldecode($response);

                error_log("Response is " . json_encode($response));

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

    function load($path, $key_field = null){
        error_log('Load 1');
       if (!is_file($path)) {
            throw new \Exception('File does not exist ' . $path, \Application::ERR_CODE_COMMON);
        }
             error_log('Load 2');
   
        $fp = fopen($path, 'r');
             error_log('Load 3');

        $header_array = fgetcsv($fp);
              error_log('Load 4');
       $use_key = !is_null($key_field) && in_array($key_field, $header_array);
                error_log('Load 5');
     $csv = null;
              error_log('Load 6');
       while ($field_array = fgetcsv($fp)) {
            if ($use_key !== false) {
                $row = array_combine($header_array, $field_array);
                $csv[$row[$key_field]] = $row;
            } else {
                $row = array_combine($header_array, $field_array);
                $csv[] = $row;
            }
        }
             error_log('Load 7');
        fclose($fp);
              error_log('Load 8');
       return $csv;
    }
}
