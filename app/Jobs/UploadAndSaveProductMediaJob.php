<?php

namespace App\Jobs;

use App\Models\Media;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UploadAndSaveProductMediaJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    protected $media;
    protected string $mediaType;

    protected int $productId;
    public function __construct($media,$mediaType,$productId)
    {
        $this->media=$media;
        $this->mediaType=$mediaType;
        $this->productId=$productId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->mediaType == 'image'){
            $media = $this->media->getRealPath()->storeOnCloudinary('products/images');
            Media::create([
                'url'=>$media->getSecurePath(),
                'type'=>'image',
                'product_id'=>$this->productId,
            ]);
        }else{
            $media = $this->media->getRealPath()->storeOnCloudinary('products/videos');
            Media::create([
                'url'=>$media->getSecurePath(),
                'type'=>'video',
                'product_id'=>$this->productId,
            ]);
        }

    }
}
