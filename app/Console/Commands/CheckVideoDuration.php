<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;

class CheckVideoDuration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'video:duration {--type=}';

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
     * @return bool
     * @throws \getid3_exception
     */
    public function handle()
    {
        $type = $this->option('type');

        if (!$type) {
            $this->error("Type is missing.");

            return false;
        }

        $storagePath = storage_path("app/videos/$type") .'/';

        if (!Storage::exists($storagePath)) {
            $this->error("Folder on path: $storagePath does not exists.");

            return false;
        }

        $filesCollection = File::allFiles($storagePath);

        $getID3 = new \getID3;

        $orderedArray = [];

        /** @var SplFileInfo $file */
        foreach ($filesCollection as $key => $file) {
            try {
                $fileName = $file->getFilenameWithoutExtension();

                $fileNameArray = explode('.', $fileName);

                $videoFileData = $getID3->analyze($file);
                $duration = date('i:s', $videoFileData['playtime_seconds']);

                $index = (int) $fileNameArray[0];

                $orderedArray[$index] = "$fileName - $duration";
            } catch (\Exception $exception) {
                dd($filesCollection[$key]);
                dd($exception, $videoFileData, $filesCollection);

                dd("Failed at $key");
            }
        }

        ksort($orderedArray);

        foreach ($orderedArray as $item) {
            $this->info("$item \n");
        }

        $this->info("Completed.");
    }
}
