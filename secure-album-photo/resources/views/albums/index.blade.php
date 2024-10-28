@extends('layouts.app')

@section('content')
<div class="container mx-auto">
    <div class="flex">
        <!-- Section des albums -->
        <div class="w-1/2">
            <h1 class="text-2xl font-bold mb-4">Mes Albums</h1>
            <a href="{{ route('albums.create') }}" class="btn btn-primary">Créer un nouvel album</a>
            <div class="mt-4">
                @foreach($albums as $album)
                    <div class="bg-white shadow-md rounded-lg mb-4 p-4" data-encrypted-title="{{ $album->title }}" data-encrypted-key="{{ $album->getEncryptedSymmetricKeyForUser(Auth::id()) }}">
                        <div class="card-body">
                            <h5 class="text-xl font-semibold text-blue-600 album-title"></h5>
                            <!-- Indicateur de propriété et de partage -->
                            @if(Auth::id() === $album->user_id)
                                <p class="text-gray-500">Propriétaire</p>
                            @elseif($album->sharedWith->contains(Auth::id()))
                                <p class="text-gray-500">Partagé avec vous</p>
                            @endif

                            <!-- Actions autorisées -->
                            @if(Auth::id() === $album->user_id || $album->sharedWith->contains(Auth::id()))
                                <a href="{{ route('albums.show', $album) }}" class="btn btn-info inline-block bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">Voir</a>
                                @if(Auth::id() === $album->user_id)
                                    <form action="{{ route('albums.destroy', $album) }}" method="POST" class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger inline-block bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600">Supprimer</button>
                                    </form>
                                @endif
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Section des images standalone -->
        <div class="w-1/2 ml-4">
            <h1 class="text-2xl font-bold mb-4">Mes Images</h1>

        <!-- Formulaire d'upload de photo -->
        <form action="{{ route('photos.store') }}" id="photoForm" class="w-1/3" method="POST">
            @csrf
            <div class="form-group mb-3">
                <label for="photo" class="block text-sm font-medium text-gray-700">Ajouter une photo</label>
                <input type="file" class="form-control w-full mt-1 border-gray-300 rounded-md" id="photo" name="photo" required>
            </div>
            <input type="hidden" id="encryptedPhoto" name="encryptedPhoto">
            <input type="hidden" id="encryptedFilename" name="encryptedFilename">
            <input type="hidden" id="encryptedSymmetricKey" name="encryptedSymmetricKey">
            <button type="submit" class="w-full bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">Uploader</button>
        </form>

            <!-- Affichage des images -->
            <div class="mt-4 overflow-auto">
                @foreach($photos as $photo)
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">{{ $photo->filename }}</h5>
                        <img src="" class="photo-image img-fluid" alt="Photo" data-photo-id="{{ $photo->id }}" data-photo-url="{{ route('photos.show', $photo) }}" data-encrypted-key-photo="{{ $photo->getEncryptedSymmetricKeyForUser(Auth::id()) }}">
                        
                        @if(Auth::id() === $photo->user_id)
                            <p class="text-gray-500">Propriétaire</p>
                            @if(Auth::id() === $photo->user_id || $photo->sharedWith->contains(Auth::id()))
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
</div>

<script>
document.addEventListener('DOMContentLoaded', async () => {
    const albumElements = document.querySelectorAll('[data-encrypted-title]');

    for (const albumElement of albumElements) {
        const encryptedTitle = albumElement.dataset.encryptedTitle;
        const encryptedSymmetricKey = albumElement.dataset.encryptedKey;

        const decryptedTitle = await decryptAlbumTitle(encryptedTitle, encryptedSymmetricKey);
        albumElement.querySelector('.album-title').textContent = decryptedTitle;
    }

    const photoElements = document.querySelectorAll('.photo-image');

    for (const photoElement of photoElements) {
        const photoUrl = photoElement.dataset.photoUrl;
        const encryptedSymmetricKey = photoElement.dataset.encryptedKeyPhoto;
        console.log("encryptedSymmetricKey: ", encryptedSymmetricKey);
        const symmetricKey = await decryptSymmetricKey(encryptedSymmetricKey);
        console.log("symmetricKey: ", symmetricKey);

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
            });
    }

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

    async function decryptAlbumTitle(encryptedTitle, encryptedSymmetricKey) {
    const [ivBase64, encryptedTitleBase64] = encryptedTitle.split(':');
    const iv = new Uint8Array(atob(ivBase64).split('').map(char => char.charCodeAt(0)));
    const encryptedTitleBuffer = new Uint8Array(atob(encryptedTitleBase64).split('').map(char => char.charCodeAt(0)));

    const encryptedSymmetricKeyBuffer = new Uint8Array(atob(encryptedSymmetricKey).split('').map(char => char.charCodeAt(0)));

    // Récupérer la clé privée de l'utilisateur depuis le localStorage
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

    // Décrypter la clé symétrique avec la clé privée de l'utilisateur
    const symmetricKeyBuffer = await crypto.subtle.decrypt(
        { name: 'RSA-OAEP' },
        privateKey,
        encryptedSymmetricKeyBuffer
    );

    const symmetricKey = await crypto.subtle.importKey(
        'raw',
        symmetricKeyBuffer,
        { name: 'AES-GCM' },
        true,
        ['decrypt']
    );

    // Décrypter le titre de l'album avec la clé symétrique
    const decryptedTitleBuffer = await crypto.subtle.decrypt(
        { name: 'AES-GCM', iv: iv },
        symmetricKey,
        encryptedTitleBuffer.buffer
    );

    return new TextDecoder().decode(decryptedTitleBuffer);
}

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

async function decryptPhoto(encryptedPhotoPath, encryptedSymmetricKey) {
    const response = await fetch(encryptedPhotoPath);
    const encryptedPhotoArrayBuffer = await response.arrayBuffer();

    const [ivBase64, encryptedPhotoBase64] = new Uint8Array(encryptedPhotoArrayBuffer);
    const iv = new Uint8Array(atob(ivBase64).split('').map(char => char.charCodeAt(0)));
    const encryptedPhotoBuffer = new Uint8Array(atob(encryptedPhotoBase64).split('').map(char => char.charCodeAt(0)));

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

    const symmetricKey = await crypto.subtle.importKey(
        'raw',
        symmetricKeyBuffer,
        { name: 'AES-GCM' },
        true,
        ['decrypt']
    );

    const decryptedPhotoBuffer = await crypto.subtle.decrypt(
        { name: 'AES-GCM', iv: iv },
        symmetricKey,
        encryptedPhotoBuffer.buffer
    );

    return new Blob([decryptedPhotoBuffer]);
    }
});

async function decryptAlbumTitle(encryptedTitle, encryptedSymmetricKey) {
    const [ivBase64, encryptedTitleBase64] = encryptedTitle.split(':');
    const iv = new Uint8Array(atob(ivBase64).split('').map(char => char.charCodeAt(0)));
    const encryptedTitleBuffer = new Uint8Array(atob(encryptedTitleBase64).split('').map(char => char.charCodeAt(0)));

    const encryptedSymmetricKeyBuffer = new Uint8Array(atob(encryptedSymmetricKey).split('').map(char => char.charCodeAt(0)));

    // Récupérer la clé privée de l'utilisateur depuis le localStorage
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

    // Décrypter la clé symétrique avec la clé privée de l'utilisateur
    const symmetricKeyBuffer = await crypto.subtle.decrypt(
        { name: 'RSA-OAEP' },
        privateKey,
        encryptedSymmetricKeyBuffer
    );

    const symmetricKey = await crypto.subtle.importKey(
        'raw',
        symmetricKeyBuffer,
        { name: 'AES-GCM' },
        true,
        ['decrypt']
    );

    // Décrypter le titre de l'album avec la clé symétrique
    const decryptedTitleBuffer = await crypto.subtle.decrypt(
        { name: 'AES-GCM', iv: iv },
        symmetricKey,
        encryptedTitleBuffer.buffer
    );

    return new TextDecoder().decode(decryptedTitleBuffer);
}

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
</script>

<script>
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

        // Generate a random symmetric key for AES-GCM
        const symmetricKey = await crypto.subtle.generateKey(
            { name: 'AES-GCM', length: 256 },
            true,
            ['encrypt', 'decrypt']
        );

        const iv = crypto.getRandomValues(new Uint8Array(12));
        const encryptedContentBuffer = await crypto.subtle.encrypt(
            { name: 'AES-GCM', iv: iv },
            symmetricKey,
            arrayBuffer
        );

        const encryptedFilenameBuffer = await crypto.subtle.encrypt(
            { name: 'AES-GCM', iv: iv },
            symmetricKey,
            new TextEncoder().encode(file.name)
        );

        // Encrypt the symmetric key with the user's public key
        const publicKeyString = localStorage.getItem('publicKey');
        if (!publicKeyString) {
            throw new Error('Public key not found in localStorage');
        }

        const publicKeyArray = new Uint8Array(JSON.parse(publicKeyString));
        const publicKey = await crypto.subtle.importKey(
            'spki',
            publicKeyArray.buffer,
            { name: 'RSA-OAEP', hash: 'SHA-256' },
            true,
            ['encrypt']
        );

        const symmetricKeyBuffer = await crypto.subtle.exportKey('raw', symmetricKey);
        const encryptedSymmetricKeyBuffer = await crypto.subtle.encrypt(
            { name: 'RSA-OAEP' },
            publicKey,
            symmetricKeyBuffer
        );

        const encryptedContent = btoa(String.fromCharCode(...new Uint8Array(encryptedContentBuffer)));
        const encryptedFilename = btoa(String.fromCharCode(...new Uint8Array(encryptedFilenameBuffer)));
        const ivBase64 = btoa(String.fromCharCode(...iv));
        const encryptedSymmetricKey = btoa(String.fromCharCode(...new Uint8Array(encryptedSymmetricKeyBuffer)));

        document.getElementById('encryptedPhoto').value = `${ivBase64}:${encryptedContent}`;
        document.getElementById('encryptedFilename').value = `${ivBase64}:${encryptedFilename}`;
        document.getElementById('encryptedSymmetricKey').value = encryptedSymmetricKey;

        // Soumettre le formulaire après avoir chiffré les données
        event.target.submit();
    };
    reader.readAsArrayBuffer(file);
});
</script>
@endsection
