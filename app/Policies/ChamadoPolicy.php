<?php

namespace App\Policies;

use App\Models\Chamado;
use App\Models\Orcamento;
use App\Models\User;

class ChamadoPolicy
{
    /**
     * O admin (Márcio/equipe) vê todos os chamados.
     * O cliente só vê os chamados da empresa a que está vinculado.
     */
    public function view(User $user, Chamado $chamado): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->cliente_id !== null && (int) $user->cliente_id === (int) $chamado->cliente_id;
    }

    /**
     * Só abre chamado quem é admin ou está vinculado a uma empresa.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->cliente_id !== null;
    }

    public function cancelar(User $user, Chamado $chamado): bool
    {
        return $this->view($user, $chamado) && ! $chamado->estaEncerrado();
    }

    /**
     * Quem vê o chamado pode enviar anexos enquanto ele estiver em aberto.
     */
    public function anexar(User $user, Chamado $chamado): bool
    {
        return $this->view($user, $chamado) && ! $chamado->estaEncerrado();
    }

    /**
     * O admin vê qualquer orçamento; o cliente só depois que o Márcio envia.
     */
    public function verOrcamento(User $user, Chamado $chamado, Orcamento $orcamento): bool
    {
        if ((int) $orcamento->chamado_id !== $chamado->id || ! $this->view($user, $chamado)) {
            return false;
        }

        return $user->isAdmin() || $orcamento->visivelParaCliente();
    }

    /**
     * Aprovar ou recusar: o cliente da empresa, ou o admin registrando a resposta recebida por outro canal.
     */
    public function responderOrcamento(User $user, Chamado $chamado, Orcamento $orcamento): bool
    {
        return $this->verOrcamento($user, $chamado, $orcamento)
            && $orcamento->status === Orcamento::ENVIADO
            && ! $chamado->estaEncerrado();
    }
}
