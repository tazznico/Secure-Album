@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Welcome to your Dashboard</h1>
    <p>Your secure album management starts here.</p>
    <a href="{{ route('albums.index') }}">vers l'index</a>
</div>

<script>
document.addEventListener('DOMContentLoaded', async () => {
    console.log("DOM loaded");
    if (!localStorage.getItem('publicKey') || !localStorage.getItem('privateKey')) {
        try {
            console.log("Generating key pair...");
            const keyPair = await generateKeyPair();
            console.log("Key pair generated:", keyPair);

            const publicKeyString = JSON.stringify(Array.from(new Uint8Array(keyPair.publicKey)));
            const privateKeyString = JSON.stringify(Array.from(new Uint8Array(keyPair.privateKey)));

            console.log("Storing public key in localStorage...");
            localStorage.setItem('publicKey', publicKeyString);
            console.log("Public key stored:", publicKeyString);

            console.log("Storing private key in localStorage...");
            localStorage.setItem('privateKey', privateKeyString);
            console.log("Private key stored:", privateKeyString);

            console.log("Sending public key to server...");
            await sendPublicKeyToServer(publicKeyString);
            console.log("Public key sent to server");

        } catch (error) {
            console.error("An error occurred:", error);
        }
    } else {
        console.log("Keys already exist in localStorage");
    }

    // Redirection après la génération et le stockage des clés
    console.log("Redirecting to albums.index...");
    window.location.href = "{{ route('albums.index') }}";
});

async function generateKeyPair() {
    const keyPair = await crypto.subtle.generateKey(
        {
            name: "RSA-OAEP",
            modulusLength: 2048,
            publicExponent: new Uint8Array([1, 0, 1]),
            hash: { name: "SHA-256" }
        },
        true,
        ["encrypt", "decrypt"]
    );

    const publicKey = await crypto.subtle.exportKey("spki", keyPair.publicKey);
    const privateKey = await crypto.subtle.exportKey("pkcs8", keyPair.privateKey);

    return { publicKey, privateKey };
}

async function sendPublicKeyToServer(publicKey) {
    const response = await fetch("{{ route('store.public.key') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ publicKey: publicKey })
    });

    if (!response.ok) {
        throw new Error('Failed to store public key');
    } else {
        console.log("Public key successfully stored on server");
    }
}
</script>
@endsection
