<header class="bg-white shadow">
    <nav class="container mx-auto px-6 py-4 flex flex-wrap justify-between items-center gap-3">
        <a href="/chamados" class="text-xl font-bold text-blue-600">OiCram OS</a>
        <ul class="flex flex-wrap gap-x-4 gap-y-2 items-center">
            @auth
                @role('admin')
                    <li><a href="{{ route('dashboard') }}" class="text-gray-600 hover:text-blue-600">Painel</a></li>
                @endrole
                <li><a href="/chamados" class="text-gray-600 hover:text-blue-600">Listar OS</a></li>
                <li><a href="/chamados/create" class="text-gray-600 hover:text-blue-600">Nova OS</a></li>
                @role('admin')
                    <li x-data="{ aberto: false }" class="relative" @click.outside="aberto = false" @keydown.escape="aberto = false">
                        <button type="button" @click="aberto = ! aberto" :aria-expanded="aberto" class="text-gray-600 hover:text-blue-600">
                            Cadastros &#9662;
                        </button>
                        <ul x-show="aberto" x-cloak class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg border border-gray-100 py-1 z-10">
                            <li><a href="{{ route('admin.empresas.index') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-50">Empresas</a></li>
                            <li><a href="{{ route('admin.usuarios.index') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-50">Usuários</a></li>
                            <li><a href="{{ route('admin.etapas.index') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-50">Etapas</a></li>
                            <li><a href="{{ route('admin.tipos-chamado.index') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-50">Tipos de chamado</a></li>
                            <li><a href="{{ route('admin.prazos-sla.edit') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-50">Prazos de SLA</a></li>
                        </ul>
                    </li>
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
