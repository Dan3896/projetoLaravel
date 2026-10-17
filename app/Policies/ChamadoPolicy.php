<?php

namespace App\Policies;

use App\Models\Chamado;
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
}
