<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpClient\HttpClient;
use Illuminate\Console\Command;

class DownloadVideos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'download:videos {--type=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        ini_set('max_execution_time', -1);
        ini_set("memory_limit",-1);

        $type = $this->option('type');

        if (!$type) {
            $this->error("Type is missing.");

            return false;
        }

        $response = Http::get('https://collectapiblocks.herokuapp.com/api/get', [
            'pass' => 'p9HmKjx24qosIXL7u7ao',
            'type' => $type,
        ]);

        if ($response->successful()) {

            $apiObject = $response->json();

            if (isset($apiObject[0]) && isset($apiObject[0]['data'])) {
                $storagePath = storage_path("app/videos/$type") .'/';

                if (!Storage::exists($storagePath)) {
                    Storage::makeDirectory($storagePath);
                } else {
                    $filesCollection = File::allFiles($storagePath);

                    $filesCount = count($filesCollection);
                }

                $dataArray = $apiObject[0]['data'];

                $dataArrayCount = count($dataArray);

                $fileCounter = 0;

                $bar = $this->output->createProgressBar($dataArrayCount);

                $bar->start();

                $filesCollection = File::allFiles($storagePath);

                $fileNameArray = [];

                /** @var SplFileInfo $file */
                foreach ($filesCollection as $file) {
                    $fileName = $file->getFilenameWithoutExtension();

                    array_push($fileNameArray, $fileName);
                }

                foreach ($dataArray as $key => $item) {
                    $url = "https://fast.wistia.net/embed/iframe/$item?videoFoam=true";

                    foreach ($fileNameArray as $fileName) {
                        if (strpos($fileName, $item) != false) {
                            $fileCounter++;
                            $bar->advance();
                            continue 2;
                        }
                    }

                    $fileCounter++;

                    $client = HttpClient::create([
                        'timeout' => 3600,
                        'verify_peer' => false
                    ]);

                    $browser = new HttpBrowser($client, null);

                    $crawler = $browser->request('GET', $url);

                    /** @var Crawler $titleNode */
                    $titleNode = $crawler->filter('meta[name="twitter:title"]')->first();

                    $videoTitle = $titleNode->attr('content');

                    $videoTitleArray = explode('.', $videoTitle);

                    $extension = $videoTitleArray[count($videoTitleArray) - 1];
                    unset($videoTitleArray[count($videoTitleArray) - 1]);

                    $videoTitle = implode('', $videoTitleArray);

                    $videoTitle = trim(strtolower(preg_replace('/[^a-zA-Z]/', ' ', $videoTitle)));

                    $videoTitle = str_replace(' ', '-', $videoTitle);

                    $titleFormatted = $fileCounter . ".$videoTitle-$item.$extension";

                    $videoInitScript = $crawler->filter('script')->last();

                    $videoInitScriptHtml = $videoInitScript->html();

                    $initScriptArray = explode('https://embed-ssl.wistia.com/deliveries/', $videoInitScriptHtml);

                    $targetPart = $initScriptArray[1];

                    $binary = str_replace("'", "", str_replace('"', "", explode(',', $targetPart)[0]));

                    $binaryUrl = "https://embed-ssl.wistia.com/deliveries/$binary";


                    $downloadPath = $storagePath . "/$titleFormatted";

                    $fp = fopen ($downloadPath, 'w+');
                    $curl = curl_init();
                    curl_setopt($curl,CURLOPT_URL,$binaryUrl);
                    curl_setopt($curl, CURLOPT_TIMEOUT, 3600);
                    curl_setopt($curl, CURLOPT_FILE, $fp);
                    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($curl, CURLOPT_NOPROGRESS, 0);
//                    curl_setopt($curl, CURLOPT_PROGRESSFUNCTION, function ($dltotal, $dlnow, $ultotal, $ulnow) {
////                        echo "$dltotal\n";
////                        echo "$dlnow\n";
//                    });

                    curl_exec($curl);
                    fclose($fp);

                    $bar->advance();
                    $this->info("\n Download - $titleFormatted is completed.");
                }

                $bar->finish();

                dd("Success .All is done.");
            }


            dd('kraj');
        }

        dd($response->body());
    }
}
