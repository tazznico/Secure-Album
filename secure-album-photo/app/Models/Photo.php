<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Photo extends Model
{
    use HasFactory;

    protected $fillable = [
        'album_id',
        'path',
        'user_id',
        'symmetric_key_encrypt',
    ];

    /**
     * Une photo appartient à un album.
     */
    public function album()
    {
        return $this->belongsTo(Album::class);
    }

    /**
     * Une photo appartenient à un utilisateur.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Une photo est partagée à d'autres utilisateurs
     */
    public function sharedWith()
    {
        return $this->belongsToMany(User::class, 'photo_user')->withPivot('symmetric_key_encrypt');
    }

    /**
     * Get the encrypted symmetric key for the given user ID.
     *
     * @param int $userId
     * @return string|null
     */
    public function getEncryptedSymmetricKeyForUser($userId)
    {
        // Check if the user is the owner of the album
        if ($this->user_id === $userId) {
            return $this->symmetric_key_encrypt;
        }

        // Check if the album is shared with the user
        $shared = $this->sharedWith()->where('user_id', $userId)->first();
        if ($shared) {
            return $shared->pivot->symmetric_key_encrypt;
        }

        return null;
    }
}
