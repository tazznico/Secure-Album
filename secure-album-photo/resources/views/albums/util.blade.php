@section('content')


<script>

    /**
     * Nous prenons la clé symétrique chiffré et la déchiffront
     *  avec notre clé privé dans notre stockage
     */
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

    async function encryptSymmetricKeyForUser(symmetricKey, publicKey) {
        try {
            const encryptedSymmetricKeyBuffer = await window.crypto.subtle.encrypt(
                {
                    name: 'RSA-OAEP'
                },
                publicKey,
                symmetricKey
            );

            return btoa(String.fromCharCode(...new Uint8Array(encryptedSymmetricKeyBuffer)));
        } catch (error) {
            console.error('Error encrypting symmetric key for user:', error);
            throw error;
        }
    }
</script>


@endsection
