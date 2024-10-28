<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Album;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;

class AlbumController extends Controller
{

    /**
     * Display your all albums
     */
    public function index()
    {
        $albums = Album::with('sharedWith')->where('user_id', Auth::id())
        ->orWhereHas('sharedWith', function ($query) {
            $query->where('user_id', Auth::id());
        })->get();

        $photos = Photo::where('album_id', null)
        ->where(function ($query) {
            $query->where('user_id', Auth::id())
                ->orWhereHas('sharedWith', function ($query) {
                    $query->where('user_id', Auth::id());
                });
        })->get();

    $users = User::where('id', '!=', Auth::id())->get();

    return view('albums.index', compact('albums', 'photos', 'users'));
    }

    /**
     * Display creation album
     */
    public function create()
    {
        // 1. récupérer ma clé publique
        $user = auth()->user();

        // 2. Récupérer le nom des utilisateur et leurs clé publique (pour pouvoir les ajouter au partage)
        $recipients = User::where('id', '!=', $user->id)->get(['name', 'public_key']);

        return view('albums.create', [
            'userPublicKey' => $user,
            'userShares' => $recipients
        ]);
    }

    /**
     * try to construt a album with request
     * if construct succeffuly so save in DB
     */
    public function store(Request $request)
    {
        $request->validate([
            'encryptedTitle' => 'required',
            'encryptedSymmetricKey' => 'required',
        ]);

        $album = new Album();
        $album->title = $request->input('encryptedTitle');
        $album->user_id = auth()->id();
        $album->symmetric_key_encrypt = $request->input('encryptedSymmetricKey');
        $album->save();
    
        return redirect()->route('albums.index');
    }
    
    /**
     * If the user authorised then 
     * display your album
     */
    public function show(Album $album)
    {
        $album = Album::with('photos', 'sharedWith')->findOrFail($album->id);
        $this->authorize('view', $album);

        $users = User::where('id', '!=', Auth::id())->get();

        return view('albums.show', compact('album', 'users'));
    }
    
    /**
     * if the user authorized then
     * destroy your album in DB
     */
    public function destroy(Album $album)
    {
        $this->authorize('delete', $album);
        $album->delete();
    
        return redirect()->route('albums.index');
    }

    /**
     * 
     */
    public function share(Request $request, $id)
    {

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'encrypted_symmetric_key' => 'required|string',
        ]);


        $album = Album::findOrFail($id);
        $this->authorize('share', $album);
    
        $sharedData = [
            'user_id' => $request->user_id,
            'symmetric_key_encrypt' => $request->encrypted_symmetric_key,
        ];
    
        if ($album->sharedWith()->where('user_id', $request->user_id)->doesntExist()) {
            $album->sharedWith()->attach($request->user_id, $sharedData);
        } else {
            $album->sharedWith()->detach($request->user_id);
        }
    
        return redirect()->route('albums.show', $album);
    }
    
}
