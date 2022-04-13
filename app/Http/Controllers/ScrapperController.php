<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;

class ScrapperController extends Controller
{

    public function scrape(string $type)
    {

        ini_set('max_execution_time', 180);
        ini_set("memory_limit",-1);

        $response = Http::get('https://collectapiblocks.herokuapp.com/api/get', [
            'pass' => 'p9HmKjx24qosIXL7u7ao',
            'type' => $type,
        ]);

        if ($response->successful()) {
            $apiObject = $response->json();

            if (isset($apiObject[0]) && isset($apiObject[0]['data'])) {
                $dataArray = $apiObject[0]['data'];

                foreach ($dataArray as $item) {
                    $url = "https://fast.wistia.net/embed/iframe/$item?videoFoam=true";

                    $client = HttpClient::create([
                        'timeout' => 900,
                        'verify_peer' => false
                    ]);

                    $browser = new HttpBrowser($client, null);

                    $crawler = $browser->request('GET', $url);

                    /** @var Crawler $titleNode */
                    $titleNode = $crawler->filter('meta[name="twitter:title"]')->first();

                    $videoTitle = $titleNode ->attr('content');

                    $videoInitScript = $crawler->filter('script')->last();

                    $videoInitScriptHtml = $videoInitScript->html();

                    $initScriptArray = explode('https://embed-ssl.wistia.com/deliveries/', $videoInitScriptHtml);

                    $targetPart = $initScriptArray[1];

                    $binary = str_replace("'", "", str_replace('"', "", explode(',', $targetPart)[0]));

                    $binaryUrl = "https://embed-ssl.wistia.com/deliveries/$binary";

                    $storagePath = storage_path('app/videos/polygon-development');

                    $downloadPath = $storagePath . "/$videoTitle";

                    $fp = fopen ($downloadPath, 'w+');
                    $curl = curl_init();
                    curl_setopt($curl,CURLOPT_URL,$binaryUrl);
                    curl_setopt($curl, CURLOPT_TIMEOUT, 1000);
                    curl_setopt($curl, CURLOPT_FILE, $fp);
                    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($curl, CURLOPT_NOPROGRESS, 0);
                    curl_setopt($curl, CURLOPT_PROGRESSFUNCTION, function ($dltotal, $dlnow, $ultotal, $ulnow) {
//                        echo "$dltotal\n";
//                        echo "$dlnow\n";
                    });

                    curl_exec($curl);
                    fclose($fp);

                    //$videoBinary = file_get_contents($binaryUrl);

                    var_dump("$videoTitle done.");

                }

                dd("All is done.");
            }


            dd('kraj');
        }

        dd($response->body());


    }


//    public function scrapeTest()
//    {
//        $url = 'https://pro.eattheblocks.com/courses/enrolled/1563075';
//        $domain = 'https://pro.eattheblocks.com';
//
//
//
//        $cookies = 'ahoy_visitor=26ff51c4-325d-4dd0-a305-2c4c22dc3f1b
// ahoy_visit=c0ccbe61-04a4-4d01-8f95-938b4e568bd2
// _afid=26ff51c4-325d-4dd0-a305-2c4c22dc3f1b
// aid=26ff51c4-325d-4dd0-a305-2c4c22dc3f1b
// ajs_anonymous_id=%22abd88898-d5ca-4d1f-bbad-d0334f8ec839%22
// _hp2_id.318805607=%7B%22userId%22%3A%22243941972902171%22%2C%22pageviewId%22%3A%225007235229784877%22%2C%22sessionId%22%3A%224776553706657125%22%2C%22identity%22%3Anull%2C%22trackerVersion%22%3A%224.0%22%7D
// wistiaVisitorKey=d60b0d5_1a3cde3f-295a-4f54-8067-9e233fa99c5d-cce560205-1f7a93004ecb-68c7
// signed_in=true
// aid=26ff51c4-325d-4dd0-a305-2c4c22dc3f1b
// ajs_user_id=%2268528583%22
// ajs_group_id=null
// _ga=GA1.2.295942234.1642684398
// _gid=GA1.2.1711273858.1642684398';
//
//        $cookieArray = explode(PHP_EOL, $cookies);
//
//        foreach ($cookieArray as &$cookie) {
//            $cookie = trim($cookie);
//        }
//
//        $jar = new CookieJar();
//
//
//        foreach ($cookieArray as $cookie) {
//            $singleCookieArray = explode('=', $cookie);
//            $jar->set(new Cookie($singleCookieArray[0], $singleCookieArray[1], null, null, $domain));
//        }
//
//        $jar->set(new Cookie('__cf_bm', 'mCCKnqNdJ1OgnRUJ6w8K8WotdsVPyzStvx4IjzR6D.Y-1642689069-0-AWolmJY3ic+XY/kWqh9+0SaC7+5OChcLNlqq/UC+lQNACDUHDeiklr3o26Q+GRG8MLNyAPSqSls/xv+jl+TC3cOjdi9KnguqCH+E+ENe+JUU0eQLZWKVFEXhhnEdW4+nJboTaDVXXlP7eKpe8UZU0rEBD5qJtmkOvuNk0yQxYYdc', null, null, $domain));
//        $jar->set(new Cookie('__cfruid', 'c8260e43423a66a1b28905bc54538881cba9dc6c-1642684395', null, null, $domain));
//        $jar->set(new Cookie('_session_id', '4f34dc91781c49cde3b69029e8e8a68c', null, null, $domain));
//        $jar->set(new Cookie('site_preview', 'logged_in', null, null, $domain));
//        $jar->set(new Cookie('sk_y0z35q9v_access', 'eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJ1c2VyIiwiaWF0IjoxNjQyNjg5MDkxLCJqdGkiOiI3MTZjZjU4Ni1mYmUwLTRiNzYtYjRhMi03MGU0ZjgyNTQwZjciLCJpc3MiOiJza195MHozNXE5diIsInN1YiI6Ijk0MDZkODliLTJjNGMtNDFlZC1iOTMxLTg4Yjk2MjJkMmE4MCJ9.XcVHCpQypTKuxRR7rD8KbWZ_qZArKw21T8NpXiSga70', null, null, $domain));
//
//
//        $client = HttpClient::create([
//            'timeout' => 900,
//            'verify_peer' => false
//        ]);
//        $browser = new HttpBrowser($client, null, $jar);
////
////        $crawler = $browser->request('GET', $url);
////
////        $coursesArray = [];
////
////        $crawler->filter('ul[class="section-list"] > li')->each(function ($node) use (&$coursesArray) {
////            $resultArray = $node->filter('[data-ss-course-id]')->each(function ($subNode){
////                /** @var \DOMElement $node */
////                $node = $subNode->getNode(0);
////
////                $courseId = $node->getAttribute('data-ss-course-id');
////                $lectureId = $node->getAttribute('data-ss-lecture-id');
////                $nodeText = $subNode->text();
////
////                return [
////                    'text' => $nodeText,
////                    'course' => $courseId,
////                    'lecture' => $lectureId,
////                ];
////            });
////
////            array_push($coursesArray, $resultArray[0]);
////        });
//
//
//        $coursesJson = '[{"text":"Start 1.1. Julien => Why should you take this course? (1:37)","course":"1563075","lecture":"35748563"},{"text":"Start 1.2. Instructor course introduction (1:22)","course":"1563075","lecture":"35748564"},{"text":"Start 2.1. Polygon Ecosystem - Course Intro (1:49)","course":"1563075","lecture":"35750486"},{"text":"Start 2.2. Basics of Polygon Network (6:29)","course":"1563075","lecture":"35748674"},{"text":"Start 2.3. Polygon Architecture 1\/2 (3:21)","course":"1563075","lecture":"35748681"},{"text":"Start 2.4. Polygon Architecture 2\/2 (6:06)","course":"1563075","lecture":"35748694"},{"text":"Start 2.5. Asset Flow in Plasma Bridge (2:07)","course":"1563075","lecture":"35748704"},{"text":"Start 2.6. PoS Bridge (Deposit) (4:44)","course":"1563075","lecture":"35748726"},{"text":"Start 2.7. PoS Bridge (Withdraw) (1:43)","course":"1563075","lecture":"35748740"},{"text":"Start 2.8. PolygonScan - Block Explorer (10:24)","course":"1563075","lecture":"35748749"},{"text":"Start 2.9. Polygon API Services (9:26)","course":"1563075","lecture":"35748767"},{"text":"Start 2.10. Polygon Gas Station (2:39)","course":"1563075","lecture":"35748798"},{"text":"Start 2.11. Q&A","course":"1563075","lecture":"35780639"},{"text":"Start 3.1. Course Introduction (2:26)","course":"1563075","lecture":"35819920"},{"text":"Start 3.2. Polygon SDK (2:38)","course":"1563075","lecture":"35820878"},{"text":"Start 3.3. Initialization of Plasma Client (12:54)","course":"1563075","lecture":"35820909"},{"text":"Start 3.4. Demo For Usage of Plasma & PoS API (11:51)","course":"1563075","lecture":"35820918"},{"text":"Start 3.5. Q&A","course":"1563075","lecture":"35842500"},{"text":"Start 4.1. Course Introduction (1:12)","course":"1563075","lecture":"35819921"},{"text":"Start 4.2. Metamask Configuration (1:25)","course":"1563075","lecture":"35819927"},{"text":"Start 4.3. Polygon and Goerli Faucet (10:25)","course":"1563075","lecture":"35819934"},{"text":"Start 4.4 Wallet Connect Provider (7:20)","course":"1563075","lecture":"35819941"},{"text":"Start 4.5. Transfer Assets Using Polygon PoS & Plasma Bridges (35:09)","course":"1563075","lecture":"35820723"},{"text":"Start 4.6. Q&A","course":"1563075","lecture":"35842373"},{"text":"Start 5.1. Course Introduction (0:48)","course":"1563075","lecture":"35819958"},{"text":"Start 5.2. State Transfer (9:24)","course":"1563075","lecture":"35819964"},{"text":"Start 5.3. Fx-Portal [Meta Bridge] (3:15)","course":"1563075","lecture":"35819965"},{"text":"Start 5.4. Fx-Bridge use cases Demo (17:26)","course":"1563075","lecture":"35819986"},{"text":"Start 6.1. Course Introduction (0:38)","course":"1563075","lecture":"35819988"},{"text":"Start 6.2. Deploying and Interacting with Remix (5:43)","course":"1563075","lecture":"35819989"},{"text":"Start 6.3. Deploying and Interacting with Hardhat (4:26)","course":"1563075","lecture":"35819990"},{"text":"Start 6.4. Deploying and Interacting with Truffle (6:52)","course":"1563075","lecture":"35819991"},{"text":"Start 6.5. Q&A","course":"1563075","lecture":"35842129"},{"text":"Start 7.1. Course Introduction (2:48)","course":"1563075","lecture":"35820117"},{"text":"Start 7.2. Polygon Asset Mapping UI (4:10)","course":"1563075","lecture":"35820118"},{"text":"Start 7.3. Mapping Assets Using PoS (26:27)","course":"1563075","lecture":"35820121"},{"text":"Start 7.4. Polygon Mintable Assets (9:41)","course":"1563075","lecture":"35820127"},{"text":"Start 7.5. Q&A","course":"1563075","lecture":"35842013"},{"text":"Start 8.1. Course Introduction (3:04)","course":"1563075","lecture":"35820134"},{"text":"Start 8.2. Introduction to Meta Transactions (8:52)","course":"1563075","lecture":"35820135"},{"text":"Start 8.3. Biconomy (4:37)","course":"1563075","lecture":"35820137"},{"text":"Start 8.4. Biconomy Mexa SDK (12:23)","course":"1563075","lecture":"35820139"},{"text":"Start 8.5. Q&A","course":"1563075","lecture":"35841966"},{"text":"Start 9.1. Course Introduction (0:41)","course":"1563075","lecture":"35820355"},{"text":"Start 9.2. Validator (1:31)","course":"1563075","lecture":"35820356"},{"text":"Start 9.3. Node Technical requirements (0:26)","course":"1563075","lecture":"35820358"},{"text":"Start 9.4. Node Setup (6:07)","course":"1563075","lecture":"35820359"},{"text":"Start 9.5. Q&A","course":"1563075","lecture":"35840936"},{"text":"Start 10.1. Course Introduction (0:44)","course":"1563075","lecture":"35820484"},{"text":"Start 10.2. The Purpose of Smart Contracts (4:57)","course":"1563075","lecture":"35820486"},{"text":"Start 10.3. The Oracle Problem (5:21)","course":"1563075","lecture":"35820487"},{"text":"Start 10.4. Chainlink Features (11:38)","course":"1563075","lecture":"35820489"},{"text":"Start 10.5. Deploying a Hybrid Smart Contract (17:20)","course":"1563075","lecture":"35820490"},{"text":"Start 10.6. Hardhat (16:12)","course":"1563075","lecture":"35820532"},{"text":"Start 10.7. Q&A","course":"1563075","lecture":"35840750"}]';
//
//
//        $coursesArray = json_decode($coursesJson, true);
//
//        foreach ($coursesArray as $courseData) {
//            $course = $courseData['course'];
//            $lecture = $courseData['lecture'];
//
//            $url = "https://pro.eattheblocks.com/courses/$course/lectures/$lecture";
//            $crawler = $browser->request('GET', $url);
//
//            $xxx = $crawler->html();
//
//            $crawler->filter('div[class="course-mainbar"] div')->each(function ($node) use (&$coursesArray) {
//
//                $test = $node->html();
//
//
//                /** @var \DOMElement $node */
//                $node = $node->getNode(0);
//
//                $wistiaId = $node->getAttribute('data-wistia-id');
//
//                $coursesArray['wistia'] = $wistiaId;
//            });
//        }
//
//
//        $test = 'fdsfds';
//
//
//
//    }
}
