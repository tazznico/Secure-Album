<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Album extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'user_id',
        'symmetric_key_encrypt'
    ];

    /**
     * Un album appartient à un propriétaire.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Un album est partagé à d'autreutilisateurs
     */
    public function sharedWith()
    {
        return $this->belongsToMany(User::class, 'album_user')->withPivot('symmetric_key_encrypt');
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

    /**
     * Un album a plusieurs photos.
     */
    public function photos()
    {
        return $this->hasMany(Photo::class);
    }
}
