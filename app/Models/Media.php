<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use ToneflixCode\LaravelFileable\Media as TMedia;

class Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'model',
        'meta',
        'file',
        'setIndex', // Index of the file in the request
        'fileField', // Name of the file field in the request
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'meta' => 'collection',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'meta' => '{}',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'media_url',
    ];

    protected $paths = [
    ];

    protected static function booted()
    {
        static::saving(function (Media $item) {
            $key = $item->fileField ?? 'file';

            if (isset($item->paths[$item->mediable_type])) {
                $item->file = (new TMedia())->save($item->paths[$item->mediable_type], $key, $item->file, $item->setIndex);
            } else {
                $item->file = (new TMedia())->save('private.images', $key, $item->file, $item->setIndex);
            }

            unset($item->setIndex);
            unset($item->fileField);

            if (! $item->file) {
                unset($item->file);
            }
            if (! $item->meta) {
                unset($item->meta);
            }
        });

        static::deleted(function (Media $item) {
            if (isset($item->paths[$item->mediable_type])) {
                (new TMedia())->delete($item->paths[$item->mediable_type], $item->file);
            } else {
                (new TMedia())->delete('private.images', $item->file);
            }
        });
    }

    /**
     * Get the parent mediable model (album or vision board).
     */
    public function mediable()
    {
        return $this->morphTo();
    }

    /**
     * Get posibly protected URL of the media.
     *
     * @return string
     */
    protected function mediaUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                // $wt = config('app.env') === 'local' ? '?wt='.Auth::user()->window_token : '?ctx='.rand();
                if (isset($this->paths[$this->mediable_type])) {
                    return (new TMedia())->getMedia($this->paths[$this->mediable_type], $this->file);
                }

                $wt = '?preload=true';

                $superLoad = (Auth::user()?->is_admin);

                if ($superLoad) {
                    $wt = '?preload=true&wt='.Auth::user()->window_token;
                } elseif ($this->mediable && $this->mediable->user->id === (Auth::user()?->id ?? 0)) {
                    $wt = '?preload=true&wt='.$this->mediable->user->window_token;
                }

                $wt .= '&ctx='.rand();
                $wt .= '&mode='.config('app.env');
                $wt .= '&pov='.md5($this->src);

                return (new TMedia())->getMedia('private.images', $this->file).$wt;
            },
        );
    }

    /**
     * Get a shared/public URL of the image.
     *
     * @return string
     */
    protected function sharedMediaUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                // $wt = config('app.env') === 'local' ? '?wt='.Auth::user()->window_token : '?ctx='.rand();
                if (isset($this->paths[$this->mediable_type])) {
                    return (new TMedia())->getMedia($this->paths[$this->mediable_type], $this->file);
                }

                $wt = '?preload=true&shared&wt='.(Auth::user() ? Auth::user()->window_token : rand());
                $wt .= '&ctx='.rand();
                $wt .= '&mode='.config('app.env');
                $wt .= '&pov='.md5($this->file);

                return (new TMedia())->getMedia('private.images', $this->file).$wt;
            },
        );
    }
}
