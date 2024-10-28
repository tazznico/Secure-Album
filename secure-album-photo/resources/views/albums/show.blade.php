@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4 " data-encrypted-title="{{ $album->title }}" data-encrypted-key="{{ $album->getEncryptedSymmetricKeyForUser(Auth::id()) }}">
    <!-- Titre de l'album -->
    <h1 class="album-titletext-3xl font-bold text-center mb-6">{{ $album->name }}</h1>

    @if(Auth::id() === $album->user_id)
    <!-- Formulaires pour l'upload de photo et le partage d'album -->
    <div class="flex justify-between items-center mb-6">
        @if($users->isEmpty())
        <p class="text-center mt-4">Aucun utilisateur à rajouter au partage.</p>
        @else
        <div class="overflow-y-auto max-h-96">
        @foreach($users as $user)
                @if($user->id !== Auth::id())
                    <div class="flex items-center justify-between mb-2">
                        <span>{{ $user->name }}</span>
                        <form action="{{ route('albums.share', ['id' => $album->id]) }}" method="POST" class="share-album-form">
                            @csrf
                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                            <input type="hidden" name="encrypted_symmetric_key" class="encrypted-symmetric-key-album">
                            <button type="button" class="share-album-button {{ $album->sharedWith->contains($user->id) ? 'bg-red-500' : 'bg-green-500' }} text-white p-2 rounded">
                                {{ $album->sharedWith->contains($user->id) ? 'Revoke' : 'Share' }}
                            </button>
                        </form>
                    </div>
                @endif
            @endforeach
        </div>
        @endif

        <!-- Formulaire d'upload de photo -->
        <form action="{{ route('photos.storeWithAlbum', $album->id) }}" id="photoForm" class="w-1/3" method="POST">
            @csrf
            <div class="form-group mb-3">
                <label for="photo" class="block text-sm font-medium text-gray-700">Ajouter une photo</label>
                <input type="file" class="form-control w-full mt-1 border-gray-300 rounded-md" id="photo" name="photo" required>
            </div>
            <input type="hidden" id="encryptedPhoto" name="encryptedPhoto">
            <input type="hidden" id="encryptedFilename" name="encryptedFilename">
            <input type="hidden" id="encryptedSymmetricKey" name="encryptedSymmetricKey" value="{{ $album->getEncryptedSymmetricKeyForUser(Auth::id()) }}">
            <button type="submit" class="w-full bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">Uploader</button>
        </form>
    </div>
    @endif

    <!-- Titre pour les images -->
    <h2 class="album-title text-2xl font-bold text-center mb-4">Album: </h2>

<!-- Affichage des photos -->
@if($album->photos->isEmpty())
    <p class="text-center mt-4">Cet album ne contient aucune photo.</p>
@else
    <div class="mt-4">
        <div class="flex flex-row gap-10">
        @foreach($album->photos as $photo)
            <div class="flex items-center justify-between mb-2 w-full border border-gray-300 rounded-lg p-4">
                <div class="card-body">
                    <h5 class="card-title">{{ $photo->filename }}</h5>
                    <img src="" class="photo-image img-fluid" alt="Photo" data-photo-id="{{ $photo->id }}" data-photo-url="{{ route('photos.show', $photo) }}" data-encrypted-key-photo="{{ $photo->symmetric_key_encrypt }}">
                    
                    @if(Auth::id() === $photo->user_id)
                        <p class="text-gray-500">Propriétaire</p>
                        @if(Auth::id() === $album->user_id || $album->sharedWith->contains(Auth::id()))
                        <form action="{{ route('photos.destroy', $photo) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Supprimer</button>
                        </form>

                        <!-- Formulaire de partage de photo -->
                        <div class="overflow-y-auto max-h-96">
                            @foreach($users as $user)
                                @if($user->id !== Auth::id())
                                    <div class="flex items-center justify-between mb-2">
                                        <span>{{ $user->name }}</span>
                                        <form action="{{ route('photos.share', $photo) }}" method="POST" class="share-photo-form">
                                            @csrf
                                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                                            <input type="hidden" name="encrypted_symmetric_key" class="encrypted-symmetric-key-photo">
                                            <button type="submit" class="share-photo-button {{ $photo->sharedWith->contains($user->id) ? 'bg-red-500' : 'bg-green-500' }} text-white p-2 rounded">
                                                {{ $photo->sharedWith->contains($user->id) ? 'Revoke' : 'Share' }}
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                        @endif
                    @elseif($photo->sharedWith->contains(Auth::id()))
                        <p class="text-gray-500">Partagé avec vous</p>
                    @endif
                </div>
            </div>
        @endforeach
        </div>
    </div>
@endif
</div>

<script>
    /* Decryptage Photo et album */
    document.addEventListener('DOMContentLoaded', async () => {
        const albumElements = document.querySelectorAll('[data-encrypted-title]');

        for (const albumElement of albumElements) {
            try {
                const encryptedTitle = albumElement.dataset.encryptedTitle;
                const encryptedSymmetricKey = albumElement.dataset.encryptedKey;

                const decryptedTitle = await decryptAlbumTitle(encryptedTitle, encryptedSymmetricKey);
                albumElement.querySelector('.album-title').textContent = albumElement.querySelector('.album-title').textContent + decryptedTitle;
            } catch (error) {
                console.error('Error decrypting album title:', error);
            }
        }

        const photoElements = document.querySelectorAll('.photo-image');

        for (const photoElement of photoElements) {
            try {
                const photoUrl = photoElement.dataset.photoUrl;
                const albumElement = document.querySelector('[data-encrypted-title]');
                const encryptedSymmetricKey = albumElement.dataset.encryptedKey;
                const symmetricKey = await decryptSymmetricKey(encryptedSymmetricKey);

                fetch(photoUrl)
                    .then(response => response.json())
                    .then(async (data) => {
                        const { ivBase64, encryptedContentBase64 } = data;
                        const iv = new Uint8Array(atob(ivBase64).split('').map(char => char.charCodeAt(0)));
                        const encryptedContentBuffer = new Uint8Array(atob(encryptedContentBase64).split('').map(char => char.charCodeAt(0)));

                        const decryptedContentBuffer = await crypto.subtle.decrypt(
                            { name: 'AES-GCM', iv: iv },
                            symmetricKey,
                            encryptedContentBuffer.buffer
                        );

                        const blob = new Blob([decryptedContentBuffer], { type: 'image/jpeg' });
                        const url = URL.createObjectURL(blob);
                        photoElement.src = url;
                    })
                    .catch(error => {
                        console.error('Error fetching or decrypting photo:', error);
                    });
            } catch (error) {
                console.error('Error decrypting photo:', error);
            }
        }

        const shareAlbumButtons = document.querySelectorAll('.share-album-button');

        shareAlbumButtons.forEach(button => {
            button.addEventListener('click', async (event) => {
                event.preventDefault();
                
                const form = event.target.closest('.share-album-form');
                const userId = form.querySelector('input[name="user_id"]').value;

                try {
                // Récupérer la clé publique de l'utilisateur avec qui partager l'album
                const response = await fetch(`/user/${userId}/public-key`);
                const userPublicKeyPem = await response.json();

                console.log('userPublicKeyPem:', userPublicKeyPem);

                // Si userPublicKeyPem est un tableau d'octets
                if (Array.isArray(userPublicKeyPem)) {
                    const userPublicKeyArray = new Uint8Array(userPublicKeyPem);
                    const userPublicKey = await importPublicKey(userPublicKeyArray);

                    // Déchiffrer la clé symétrique de l'album avec la clé privée de l'utilisateur actuel
                    const encryptedSymmetricKey = '{{ $album->symmetric_key_encrypt }}';
                    const decryptedSymmetricKey = await decryptSymmetricKey(encryptedSymmetricKey);

                    // Convertir la clé symétrique en ArrayBuffer si ce n'est pas déjà fait
                    const symmetricKeyBuffer = await crypto.subtle.exportKey('raw', decryptedSymmetricKey);

                    // Chiffrer la clé symétrique avec la clé publique de l'utilisateur avec qui partager l'album
                    const encryptedSymmetricKeyForUser = await encryptSymmetricKeyForUser(symmetricKeyBuffer, userPublicKey);

                    // Mettre à jour le formulaire avec la clé symétrique chiffrée
                    form.querySelector('.encrypted-symmetric-key-album').value = encryptedSymmetricKeyForUser;

                    // Soumettre le formulaire
                    form.submit();
                }
                } catch (error) {
                    console.error('Error sharing album:', error);
                }
            });
        });

        const sharePhotoButtons = document.querySelectorAll('.share-photo-button');

        sharePhotoButtons.forEach(buttonPhoto => {
            buttonPhoto.addEventListener('click', async (eventPhoto) => {
                eventPhoto.preventDefault();
                
                const formPhoto = event.target.closest('.share-photo-form');
                const userIdPhoto = formPhoto.querySelector('input[name="user_id"]').value;
                const photoElement = event.target.closest('.card-body').querySelector('.photo-image');
                const encryptedSymmetricKeyPhoto = photoElement.getAttribute('data-encrypted-key-photo');

                try {
                // Récupérer la clé publique de l'utilisateur avec qui partager la photo
                console.log('userId:', userIdPhoto);
                const response = await fetch(`/user/${userIdPhoto}/public-key`);
                console.log('response:', response);
                const userPublicKeyPem = await response.json();
                console.log('userPublicKeyPem:', userPublicKeyPem);

                console.log('userPublicKeyPem:', userPublicKeyPem);

                // Si userPublicKeyPem est un tableau d'octets
                if (Array.isArray(userPublicKeyPem)) {
                    const userPublicKeyArray = new Uint8Array(userPublicKeyPem);
                    const userPublicKey = await importPublicKey(userPublicKeyArray);

                    // Déchiffrer la clé symétrique de l'album avec la clé privée de l'utilisateur actuel
                    const decryptedSymmetricKey = await decryptSymmetricKey(encryptedSymmetricKeyPhoto);

                    // Convertir la clé symétrique en ArrayBuffer si ce n'est pas déjà fait
                    const symmetricKeyBuffer = await crypto.subtle.exportKey('raw', decryptedSymmetricKey);

                    // Chiffrer la clé symétrique avec la clé publique de l'utilisateur avec qui partager la photo
                    const encryptedSymmetricKeyForUser = await encryptSymmetricKeyForUser(symmetricKeyBuffer, userPublicKey);

                    // Mettre à jour le formulaire avec la clé symétrique chiffrée
                    formPhoto.querySelector('.encrypted-symmetric-key-photo').value = encryptedSymmetricKeyForUser;

                    // Soumettre le formulaire
                    formPhoto.submit();
                }
                } catch (error) {
                    console.error('Error sharing photo:', error);
                }
            });
        });
    });

    async function importPublicKey(binaryDer) {
        return await window.crypto.subtle.importKey(
        'spki',
        binaryDer.buffer,
        {
            name: 'RSA-OAEP',
            hash: 'SHA-256'
        },
        true,
        ['encrypt']
    );
    }

    function str2ab(str) {
        const buf = new ArrayBuffer(str.length);
        const bufView = new Uint8Array(buf);
        for (let i = 0, strLen = str.length; i < strLen; i++) {
            bufView[i] = str.charCodeAt(i);
        }
        return buf;
    }

    async function decryptSymmetricKey(encryptedSymmetricKey) {
        try {
            const encryptedSymmetricKeyBuffer = Uint8Array.from(atob(encryptedSymmetricKey), c => c.charCodeAt(0));

            // Récupérer la clé privée de l'utilisateur actuel depuis le localStorage
            const privateKeyString = localStorage.getItem('privateKey');
            if (!privateKeyString) {
                throw new Error('Private key not found in localStorage');
            }

            const privateKeyArray = JSON.parse(privateKeyString);
            const privateKey = await window.crypto.subtle.importKey(
                'pkcs8',
                new Uint8Array(privateKeyArray),
                {
                    name: 'RSA-OAEP',
                    hash: 'SHA-256'
                },
                true,
                ['decrypt']
            );

            // Déchiffrer la clé symétrique
            const decryptedSymmetricKeyBuffer = await window.crypto.subtle.decrypt(
                {
                    name: 'RSA-OAEP'
                },
                privateKey,
                encryptedSymmetricKeyBuffer
            );

            return await window.crypto.subtle.importKey(
                'raw',
                decryptedSymmetricKeyBuffer,
                { name: 'AES-GCM' },
                true,
                ['encrypt', 'decrypt']
            );
        } catch (error) {
            console.error('Error decrypting symmetric key:', error);
            throw error;
        }
    }

    async function encryptSymmetricKeyForUser(symmetricKey, publicKey) {
    try {
        const encryptedSymmetricKeyBuffer = await window.crypto.subtle.encrypt(
            {
                name: 'RSA-OAEP'
            },
            publicKey,
            symmetricKey // Assurez-vous que cette clé est un ArrayBuffer
        );

        return btoa(String.fromCharCode(...new Uint8Array(encryptedSymmetricKeyBuffer)));
    } catch (error) {
        console.error('Error encrypting symmetric key for user:', error);
        throw error;
    }
}

    async function decryptAlbumTitle(encryptedTitle, encryptedSymmetricKey) {
        try {
            const [ivBase64, encryptedTitleBase64] = encryptedTitle.split(':');
            const iv = new Uint8Array(atob(ivBase64).split('').map(char => char.charCodeAt(0)));
            const encryptedTitleBuffer = new Uint8Array(atob(encryptedTitleBase64).split('').map(char => char.charCodeAt(0)));

            const symmetricKey = await decryptSymmetricKey(encryptedSymmetricKey);
            console.log("symmetricKey", symmetricKey);
            console.log("encryptedTitle", encryptedTitle);

            // Déchiffrer le titre de l'album avec la clé symétrique
            const decryptedTitleBuffer = await window.crypto.subtle.decrypt(
                { name: 'AES-GCM', iv: iv },
                symmetricKey,
                encryptedTitleBuffer.buffer
            );
            return new TextDecoder().decode(decryptedTitleBuffer);
        } catch (error) {
            console.error('Error decrypting album title:', error);
            throw error;
        }
    }
</script>

<script>
    /* Encryptage */
    document.getElementById('photoForm').addEventListener('submit', async function(event) {
    event.preventDefault();

    const fileInput = document.getElementById('photo');
    const file = fileInput.files[0];

    if (!file) {
        alert('Veuillez sélectionner une photo.');
        return;
    }

    // Check file type and size
    const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
    const maxSize = 2048 * 1024; // 2MB in bytes

    if (!allowedTypes.includes(file.type)) {
        alert('Seuls les fichiers JPEG, PNG, JPG et GIF sont autorisés.');
        return;
    }

    if (file.size > maxSize) {
        alert('La taille du fichier ne doit pas dépasser 2 Mo.');
        return;
    }
    
    const reader = new FileReader();
    reader.onload = async function() {
        const arrayBuffer = reader.result;

        const albumElement = document.querySelector('[data-encrypted-title]');
        const encryptedSymmetricKey = albumElement.dataset.encryptedKey;
        const symmetricKey = await decryptSymmetricKey(encryptedSymmetricKey);

        const iv = crypto.getRandomValues(new Uint8Array(12));
        const encryptedContentBuffer = await crypto.subtle.encrypt(
            { name: 'AES-GCM', iv: iv },
            symmetricKey,
            arrayBuffer
        );
        console.log("encryptedContentBuffer: ", encryptedContentBuffer);

        const encryptedFilenameBuffer = await crypto.subtle.encrypt(
            { name: 'AES-GCM', iv: iv },
            symmetricKey,
            new TextEncoder().encode(file.name)
        );

        const encryptedContent = btoa(String.fromCharCode(...new Uint8Array(encryptedContentBuffer)));
        const encryptedFilename = btoa(String.fromCharCode(...new Uint8Array(encryptedFilenameBuffer)));
        const ivBase64 = btoa(String.fromCharCode(...iv));
        console.log("encryptedContent: ", encryptedContent);
        console.log("encryptedFilename: ", encryptedFilename);

        document.getElementById('encryptedPhoto').value = `${ivBase64}:${encryptedContent}`;
        document.getElementById('encryptedFilename').value = `${ivBase64}:${encryptedFilename}`;

        // Soumettre le formulaire après avoir chiffré les données
        event.target.submit();
    };
    reader.readAsArrayBuffer(file);
});

async function decryptSymmetricKey(encryptedSymmetricKey) {
    const encryptedSymmetricKeyBuffer = new Uint8Array(atob(encryptedSymmetricKey).split('').map(char => char.charCodeAt(0)));

    const privateKeyString = localStorage.getItem('privateKey');
    if (!privateKeyString) {
        throw new Error('Private key not found in localStorage');
    }

    const privateKeyArray = new Uint8Array(JSON.parse(privateKeyString));
    const privateKey = await crypto.subtle.importKey(
        'pkcs8',
        privateKeyArray.buffer,
        { name: 'RSA-OAEP', hash: 'SHA-256' },
        true,
        ['decrypt']
    );

    const symmetricKeyBuffer = await crypto.subtle.decrypt(
        { name: 'RSA-OAEP' },
        privateKey,
        encryptedSymmetricKeyBuffer
    );

    return await crypto.subtle.importKey(
        'raw',
        symmetricKeyBuffer,
        { name: 'AES-GCM' },
        true,
        ['encrypt', 'decrypt']
    );
}
</script>


@endsection