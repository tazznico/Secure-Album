@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Créer un Nouvel Album</h1>
    <form id="albumForm" action="{{ route('albums.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="title">Titre de l'album</label>
            <input type="text" class="form-control" id="title" name="title" required>
        </div>
        <input type="hidden" id="encryptedTitle" name="encryptedTitle">
        <input type="hidden" id="encryptedSymmetricKey" name="encryptedSymmetricKey">
        <button type="submit" class="btn btn-primary mt-3">Créer</button>
    </form>
</div>

<script>
document.getElementById('albumForm').addEventListener('submit', async function(event) {
    event.preventDefault();
    
    const title = document.getElementById('title').value;
    const ownerPublicKey = await getOwnerPublicKey(); // Récupérer la clé publique du propriétaire

    // Générer une clé symétrique
    const symmetricKey = await crypto.subtle.generateKey(
        { name: 'AES-GCM', length: 256 },
        true,
        ['encrypt', 'decrypt']
    );

    // Chiffrer le titre de l'album avec la clé symétrique
    const iv = crypto.getRandomValues(new Uint8Array(12));
    const encryptedTitleBuffer = await crypto.subtle.encrypt(
        { name: 'AES-GCM', iv: iv },
        symmetricKey,
        new TextEncoder().encode(title)
    );

    // Exporter la clé symétrique
    const symmetricKeyBuffer = await crypto.subtle.exportKey('raw', symmetricKey);

    // Chiffrer la clé symétrique avec la clé publique du propriétaire
    const encryptedSymmetricKeyBuffer = await crypto.subtle.encrypt(
        { name: 'RSA-OAEP' },
        ownerPublicKey,
        symmetricKeyBuffer
    );

    // Convertir les buffers en base64 pour les envoyer au serveur
    const encryptedTitle = btoa(String.fromCharCode(...new Uint8Array(encryptedTitleBuffer)));
    const encryptedSymmetricKey = btoa(String.fromCharCode(...new Uint8Array(encryptedSymmetricKeyBuffer)));
    const ivBase64 = btoa(String.fromCharCode(...iv));

    document.getElementById('encryptedTitle').value = `${ivBase64}:${encryptedTitle}`;
    document.getElementById('encryptedSymmetricKey').value = encryptedSymmetricKey;

    this.submit();
});

async function getOwnerPublicKey() {
    const publicKeyString = localStorage.getItem('publicKey');
    if (!publicKeyString) {
        throw new Error('Public key not found in localStorage');
    }
    
    const publicKeyArray = new Uint8Array(JSON.parse(publicKeyString));
    return await crypto.subtle.importKey(
        'spki',
        publicKeyArray.buffer,
        { name: 'RSA-OAEP', hash: 'SHA-256' },
        true,
        ['encrypt']
    );
}
</script>
@endsection
