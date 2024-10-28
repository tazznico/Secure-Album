<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Album;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;

class PhotoController extends Controller
{

    /**
     * 
     */
    public function index()
    {
        $photos = Photo::where('user_id', Auth::id())
            ->orWhereHas('sharedWith', function ($query) {
                $query->where('user_id', Auth::id());
            })->get();

        $users = User::where('id', '!=', Auth::id())->get();

        return view('photos.index', compact('photos', 'users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'encryptedPhoto' => 'required',
            'encryptedFilename' => 'required',
            'encryptedSymmetricKey' => 'required',
        ]);

        $encryptedPhoto = $request->input('encryptedPhoto');
        $encryptedFilename = $request->input('encryptedFilename');
        $encryptedSymmetricKey = $request->input('encryptedSymmetricKey');

        // Encode the filename to be safe for storage
        $safeFilename = base64_encode($encryptedFilename) . '.enc';

        $path = 'photos/' . $safeFilename;
        Storage::disk('private')->put($path, $encryptedPhoto);

        // Save encrypted image info to the database
        $photo = new Photo();
        $photo->path = $path;
        $photo->symmetric_key_encrypt = $encryptedSymmetricKey; // Store the encrypted symmetric key
        $photo->user_id = auth()->id();
        $photo->save();

        return redirect()->route('albums.index')->with('success', 'Image encrypted and stored successfully');
    }

    public function storeWithAlbum(Request $request, Album $album)
    {
        $request->validate([
            'encryptedPhoto' => 'required',
            'encryptedFilename' => 'required',
            'encryptedSymmetricKey' => 'required',
        ]);

        $encryptedPhoto = $request->input('encryptedPhoto');
        $encryptedFilename = $request->input('encryptedFilename');
        $encryptedSymmetricKey = $request->input('encryptedSymmetricKey');

        // Encode the filename to be safe for storage
        $safeFilename = base64_encode($encryptedFilename) . '.enc';

        $path = 'photos/' . $safeFilename;
        Storage::disk('private')->put($path, $encryptedPhoto);

        // Save encrypted image info to the database
        $photo = new Photo();
        $photo->album_id = $album->id;
        $photo->path = $path;
        $photo->symmetric_key_encrypt = $encryptedSymmetricKey; // Store the encrypted symmetric key
        $photo->user_id = auth()->id();
        $photo->save();

        return redirect()->route('albums.show', $album->id)->with('success', 'Image encrypted and stored successfully');
    }

    /**
     * 
     */
    public function show(Photo $photo)
    {
        $this->authorize('view', $photo);

        $fileContent = Storage::disk('private')->get($photo->path);
        list($ivBase64, $encryptedContentBase64) = explode(':', $fileContent);

        return response()->json([
            'ivBase64' => $ivBase64,
            'encryptedContentBase64' => $encryptedContentBase64,
        ]);
    }
    
    /**
     * 
     */
    public function destroy(Photo $photo)
    {
        $photo = Photo::findOrFail($photo->id);
        
        $this->authorize('delete', $photo);

        if ($photo->user_id !== Auth::id() && !$photo->sharedWith->contains(Auth::id())) {
            abort(403, 'Unauthorized action.');
        }

        Storage::disk('private')->delete($photo->path);

        $photo->delete();
    
        return $photo->album_id ? redirect()->route('albums.show', $photo->album_id) : redirect()->route('albums.index');
    }

    /**
     * 
     */
    public function share(Request $request, $photoId)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'encrypted_symmetric_key' => 'required|string',
        ]);

        $photo = Photo::findOrFail($photoId);
        $this->authorize('share', $photo);

        $sharedData = [
            'user_id' => $request->user_id,
            'symmetric_key_encrypt' => $request->encrypted_symmetric_key,
        ];
    
        if ($photo->sharedWith()->where('user_id', $request->user_id)->doesntExist()) {
            $photo->sharedWith()->attach($request->user_id, $sharedData);
        } else {
            $photo->sharedWith()->detach($request->user_id);
        }

        return $photo->album_id ? redirect()->route('albums.show', $photo->album_id) : redirect()->route('albums.index');
    }

}

