<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Str;

class LoggedRequestResponse extends Model
{
    use HasFactory;
    
    protected $keyType = 'string';

    public $incrementing = false;

    protected ?Encrypter $contentEncrypter = null;

    protected $fillable = [
        'id',
        'partner_id',
        'ip_address',
        'endpoint',
        'action',
        'request_type',
        'request_content',
        'request_at',
        'response_status',
        'response_content',
        'response_at',
        'response_time',
    ];

    public static function booted()
    {
        static::creating(function ($model) {
            $model->id = Str::uuid();
        });
    }

    public function setRequestContentAttribute($value)
    {
        $this->attributes['request_content'] = $this->getEncrypter()->encrypt($value);
    }

    public function getRequestContentAttribute($value)
    {
        if (is_null($value)) {
            return $value;
        }
        try {
            return $this->attributes['request_content'] = $this->getEncrypter()->decrypt($value);
        } catch (DecryptException $e) {
            return $this->attributes['request_content'];
        }

    }

    public function getResponseTimeAttribute()
    {
        return $this->attributes['response_time'];
    }

    public function setResponseContentAttribute($value)
    {
        $this->attributes['response_content'] = $this->getEncrypter()->encrypt($value);
    }

    public function getResponseContentAttribute($value)
    {
        if (is_null($value)) {
            return $value;
        }
        try {
            return $this->attributes['response_content'] = $this->getEncrypter()->decrypt($value);
        } catch (DecryptException $e) {
            return $this->attributes['response_content'];
        }
    }

    protected function getEncrypter(): ?Encrypter
    {

        if (is_null($this->contentEncrypter)) {
            $key = config('app.shared_key');

            if (Str::startsWith($key = $key, $prefix = 'base64:')) {
                $key = base64_decode(Str::after($key, $prefix));
                $this->contentEncrypter = new Encrypter($key, config('app.cipher'));
            }
        }

        return $this->contentEncrypter;
    }
}
