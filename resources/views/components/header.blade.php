<header class="bg-white shadow">
    <nav class="container mx-auto px-6 py-4 flex justify-between items-center">
        <a href="/chamados" class="text-xl font-bold text-blue-600">OiCram OS</a>
        <ul class="flex space-x-4 items-center">
            @auth
                <li><a href="/chamados" class="text-gray-600 hover:text-blue-600">Listar OS</a></li>
                <li><a href="/chamados/create" class="text-gray-600 hover:text-blue-600">Nova OS</a></li>
                @role('admin')
                    <li><a href="{{ route('admin.usuarios.index') }}" class="text-gray-600 hover:text-blue-600">Usuários</a></li>
                @endrole
                <li>
                    <form method="POST" action="/logout" class="inline">
                        @csrf
                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">Sair</button>
                    </form>
                </li>
            @endauth
        </ul>
    </nav>
</header>
