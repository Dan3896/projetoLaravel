@php
    $extensoes = \App\Models\ChamadoAnexo::EXTENSOES;
    $maximoMb = \App\Models\ChamadoAnexo::TAMANHO_MAXIMO_KB / 1024;
@endphp

<input type="file" name="anexos[]" id="anexos" multiple
       accept="{{ collect($extensoes)->map(fn ($extensao) => '.' . $extensao)->implode(',') }}"
       {{ $attributes->merge(['class' => 'w-full border border-gray-300 rounded-md p-2 bg-white text-sm']) }}>
<p class="text-sm text-gray-500 mt-1">
    Até {{ \App\Models\ChamadoAnexo::MAXIMO_POR_ENVIO }} arquivos de {{ $maximoMb }} MB cada:
    {{ collect($extensoes)->implode(', ') }}.
</p>
