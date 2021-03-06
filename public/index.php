<?php
require __DIR__ . '/../vendor/autoload.php';
 
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
 
use \LINE\LINEBot;
use \LINE\LINEBot\HTTPClient\CurlHTTPClient;
use \LINE\LINEBot\MessageBuilder\MultiMessageBuilder;
use \LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use \LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
use \LINE\LINEBot\SignatureValidator as SignatureValidator;
 
$pass_signature = true;
 
// set LINE channel_access_token and channel_secret
$channel_access_token = "NK0HqRTTrF5hLWD32qNLq2u8WgDGZTeXEwYLR6EI2uLGM7ErbIW17SpS0lhOa22upIuC9Kx+9yLVGcsvbws8q7qjYxBi6cTtNciur+OssoXnmrXaqI1n/ZOILw5AIEstEvbwQNI3F05l0wixuWlUkgdB04t89/1O/w1cDnyilFU=";
$channel_secret = "6e11b4da92d69c0243d9e1ec4509f7ef";
 
// inisiasi objek bot
$httpClient = new CurlHTTPClient($channel_access_token);
$bot = new LINEBot($httpClient, ['channelSecret' => $channel_secret]);
 
$app = AppFactory::create();
$app->setBasePath("/public");
 
$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Hello World!");
    return $response;
});
 
// buat route untuk webhook
$app->post('/webhook', function (Request $request, Response $response) use ($channel_secret, $bot, $httpClient, $pass_signature) {
    // get request body and line signature header
    $body = $request->getBody();
    $signature = $request->getHeaderLine('HTTP_X_LINE_SIGNATURE');
 
    // log body and signature
    file_put_contents('php://stderr', 'Body: ' . $body);
 
    if ($pass_signature === false) {
        // is LINE_SIGNATURE exists in request header?
        if (empty($signature)) {
            return $response->withStatus(400, 'Signature not set');
        }
 
        // is this request comes from LINE?
        if (!SignatureValidator::validateSignature($body, $channel_secret, $signature)) {
            return $response->withStatus(400, 'Invalid signature');
        }
    }
    
    $data = json_decode($body, true);
    if(is_array($data['events'])){
        foreach ($data['events'] as $event)
        {
            if ($event['type'] == 'message')
            {
                if($event['message']['type'] == 'text')
                {                                      

                    // if($event['message']['text'] == "halo"){
                    //     $packageId = 1;
                    //     $stickerId = 2;
                    //     $stickerMessageBuilder = new StickerMessageBuilder($packageId, $stickerId);
                    //     $result = $bot->replyMessage($event['replyToken'], $stickerMessageBuilder);
                    // }
                    // elseif($event['message']['text'] == "siapa kamu"){
                    //     $textMessageBuilder = new TextMessageBuilder("perkenalkan namaku logi");
                    //     $result = $bot->replyMessage($event['replyToken'], $textMessageBuilder);
                    // }                    
                    // else{
                    //     $balasanSalah =

                    //     "Logi Tidak Menemukan Keyword yang Kamu Maksud.\n\nBeberapa opsi yang bisa kamu coba:\n\nHelp --- Untuk mengetahui penggunaan dan keyword.\n\nPlay --- Untuk Bermain LogiFun.\n\nAbout --- Untuk Mengetahui asal usul aplikasi LogiFun.";

                    //     $textMessageBuilder = new TextMessageBuilder($balasanSalah);
                    //     $result = $bot->replyMessage($event['replyToken'], $textMessageBuilder);
                    // } 
                    
                    switch ($event['message']['text']) {
                        case 'halo':
                            $packageId = 1;
                            $stickerId = 2;
                            $stickerMessageBuilder = new StickerMessageBuilder($packageId, $stickerId);
                            $result = $bot->replyMessage($event['replyToken'], $stickerMessageBuilder);
                            break;

                        case 'siapa kamu':
                            $textMessageBuilder = new TextMessageBuilder("perkenalkan namaku logi, aku adalah sebuah bot sederhana untuk membantu belajar rangkaian digital. Logi juga bisa kamu invite ke groupmu untuk bermain/belajar bersama teman-temanmu");
                            $result = $bot->replyMessage($event['replyToken'], $textMessageBuilder);
                            break;

                        case 'play':
                            $flexTemplate = file_get_contents("../flex_message.json");
                            $result = $httpClient->post(LINEBot::DEFAULT_ENDPOINT_BASE . '/v2/bot/message/reply', [
                                'replyToken' => $event['replyToken'],
                                'messages'   => [
                                    [
                                        'type'     => 'flex',
                                        'altText'  => 'Test Flex Message',
                                        'contents' => json_decode($flexTemplate)
                                    ]
                                ],
                            ]);
                            break;

                        default:
                            $balasanSalah = "Logi Tidak Menemukan Keyword yang Kamu Maksud.\n\nBeberapa opsi yang bisa kamu coba:\n\nhelp --- Untuk mengetahui penggunaan dan keyword.\n\nplay --- Untuk Bermain LogiFun.\n\nabout --- Untuk Mengetahui asal usul aplikasi LogiFun.";
                            $textMessageBuilder = new TextMessageBuilder($balasanSalah);
                            $result = $bot->replyMessage($event['replyToken'], $textMessageBuilder);
                            break;
                    }

                    // send same message as reply to user
                    // $result = $bot->replyText($event['replyToken'], $event['message']['text']);
 
 
                    // or we can use replyMessage() instead to send reply message
                    // $textMessageBuilder = new TextMessageBuilder($event['message']['text']);
                    // $result = $bot->replyMessage($event['replyToken'], $textMessageBuilder);
 
 
                    $response->getBody()->write($result->getJSONDecodedBody());
                    return $response
                        ->withHeader('Content-Type', 'application/json')
                        ->withStatus($result->getHTTPStatus());
                }
            }
        }
    }
// kode aplikasi nanti disini
 
});

$app->get('/pushmessage', function ($req, $response) use ($bot) {
    // send push message to user    
    $userId = 'Uaa444e2fffe9743126cd277f13082838';
    $textMessageBuilder = new TextMessageBuilder('Halo, ini pesan push');
    $result = $bot->pushMessage($userId, $textMessageBuilder);
 
    $response->getBody()->write((string) $result->getJSONDecodedBody());
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus($result->getHTTPStatus());
});

$app->run();