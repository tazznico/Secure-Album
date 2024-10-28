<?php

namespace App\Policies;

use App\Models\Photo;
use App\Models\Album;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PhotoPolicy
{

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Photo $photo): bool
    {
        // Vérifie si l'utilisateur est le propriétaire de la photo
        if ($user->id === $photo->user_id) {
            return true;
        }

        // Vérifie si la photo est partagée directement avec l'utilisateur
        if ($photo->sharedWith()->where('user_id', $user->id)->exists()) {
            return true;
        }

        // Vérifie si l'utilisateur a accès à l'album contenant la photo
        $album = Album::find($photo->album_id);
        if ($album && $album->sharedWith()->where('user_id', $user->id)->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Photo $photo): bool
    {
        return $user->id === $photo->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Photo $photo): bool
    {
        return $user->id === $photo->user_id;
    }

        /**
     * Determine whether the user can share the album.
     */
    public function share(User $user, Photo $photo): bool
    {
        return $user->id === $photo->user_id;
    }
}
