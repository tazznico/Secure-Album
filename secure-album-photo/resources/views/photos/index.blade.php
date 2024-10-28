<!-- @extends('layouts.app')

@section('content')
<div class="container">
    <h1>Mes Photos</h1>

    <form action="{{ route('photos.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="form-group">
            <label for="photo">Ajouter une photo</label>
            <input type="file" class="form-control" id="photo" name="photo" required>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Uploader</button>
    </form>

    <div class="mt-4">
        @foreach($photos as $photo)
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">{{ $photo->filename }}</h5>
                    <img src="{{ route('photos.show', $photo) }}" class="img-fluid" alt="Photo">

                    @if(Auth::id() === $photo->user_id || $photo->sharedWith->contains(Auth::id()))
                        <form action="{{ route('photos.destroy', $photo) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Supprimer</button>
                        </form>

                        <!-- Formulaire de partage de photo --
                        <form action="{{ route('photos.share', $photo) }}" method="POST" class="mt-2">
                            @csrf
                            <div class="form-group">
                                <label for="shareUser">Partager avec</label>
                                <select class="form-control" id="shareUser" name="user_id" required>
                                    @foreach($users as $user)
                                        @if($user->id !== Auth::id())
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary mt-2">Partager</button>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection -->
