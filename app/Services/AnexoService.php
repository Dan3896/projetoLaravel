<?php

namespace App\Services;

use App\Models\Chamado;
use App\Models\ChamadoAnexo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class AnexoService
{
    /**
     * Grava os arquivos em chamados/{id} e registra em chamado_anexos.
     * Se algum falhar, apaga os arquivos já gravados e relança o erro,
     * para a transação de quem chamou desfazer os registros.
     *
     * @param  array<int, UploadedFile>  $arquivos
     */
    public function salvar(Chamado $chamado, array $arquivos): void
    {
        $disco = Storage::disk(ChamadoAnexo::DISCO);
        $gravados = [];

        try {
            foreach ($arquivos as $arquivo) {
                $caminho = $disco->putFile('chamados/' . $chamado->id, $arquivo);

                if ($caminho === false) {
                    throw new RuntimeException("Não foi possível gravar o anexo {$arquivo->getClientOriginalName()}.");
                }

                $gravados[] = $caminho;

                $chamado->anexos()->create([
                    'nome_arquivo' => mb_substr($arquivo->getClientOriginalName(), 0, 255),
                    'tipo_arquivo' => $arquivo->getMimeType(),
                    'caminho' => $caminho,
                    'tamanho' => $arquivo->getSize(),
                ]);
            }
        } catch (Throwable $erro) {
            $disco->delete($gravados);

            throw $erro;
        }
    }

    public function excluir(ChamadoAnexo $anexo): void
    {
        Storage::disk(ChamadoAnexo::DISCO)->delete($anexo->caminho);
        $anexo->delete();
    }
}
