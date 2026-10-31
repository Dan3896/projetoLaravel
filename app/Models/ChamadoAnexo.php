<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChamadoAnexo extends Model
{
    // Fica no disco privado: o arquivo só sai pelo download que passa pela ChamadoPolicy.
    public const DISCO = 'local';

    public const EXTENSOES = ['png', 'jpg', 'jpeg', 'pdf', 'txt', 'log', 'zip'];

    public const MIMETYPES = [
        'image/png',
        'image/jpeg',
        'application/pdf',
        'text/plain',
        'application/zip',
        'application/x-zip-compressed',
    ];

    public const TAMANHO_MAXIMO_KB = 10240;

    public const MAXIMO_POR_ENVIO = 5;

    protected $table = 'chamado_anexos';

    protected $fillable = [
        'chamado_id',
        'nome_arquivo',
        'tipo_arquivo',
        'caminho',
        'tamanho',
    ];

    public function chamado(): BelongsTo
    {
        return $this->belongsTo(Chamado::class);
    }

    /**
     * Regras de validação de um arquivo, usadas na abertura do chamado e no envio posterior.
     */
    public static function regras(): array
    {
        return [
            'file',
            'max:' . self::TAMANHO_MAXIMO_KB,
            'extensions:' . implode(',', self::EXTENSOES),
            'mimetypes:' . implode(',', self::MIMETYPES),
        ];
    }

    public function tamanhoFormatado(): string
    {
        $bytes = (int) $this->tamanho;

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1, ',', '.') . ' MB';
        }

        return max(1, (int) round($bytes / 1024)) . ' KB';
    }
}
