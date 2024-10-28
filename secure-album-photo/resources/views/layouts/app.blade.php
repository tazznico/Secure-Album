<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Albums</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-2 flex justify-between items-center">
            <a class="text-xl font-bold text-blue-600" href="#">Albums</a>
            <ul class="flex space-x-4">
                @guest
                    <li>
                        <a class="text-gray-700 hover:text-blue-600" href="{{ route('login') }}">Connexion</a>
                    </li>
                    <li>
                        <a class="text-gray-700 hover:text-blue-600" href="{{ route('register') }}">Inscription</a>
                    </li>
                @else
                    <li>
                        <a class="text-gray-700 hover:text-blue-600" href="{{ route('albums.index') }}">Mes Albums</a>
                    </li>
                    <li>
                        <a class="text-gray-700 hover:text-blue-600" href="{{ route('logout') }}"
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            Déconnexion
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                            @csrf
                        </form>
                    </li>
                @endguest
            </ul>
        </div>
    </nav>
    <div class="container mx-auto mt-4">
        @yield('content')
    </div>
</body>
</html>
