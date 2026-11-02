// Evita envio duplo: no primeiro submit o formulário trava, os botões ficam desabilitados
// e o botão clicado mostra um spinner (animate-spin do Tailwind).

const ATRIBUTO = 'data-enviando';

function criarSpinner() {
    const spinner = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    spinner.setAttribute('viewBox', '0 0 24 24');
    spinner.setAttribute('fill', 'none');
    spinner.setAttribute('aria-hidden', 'true');
    spinner.setAttribute('data-spinner', '');
    spinner.setAttribute('class', 'animate-spin -ml-1 mr-2 inline-block h-4 w-4 align-[-2px]');
    spinner.innerHTML =
        '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>' +
        '<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>';

    return spinner;
}

function botoesDeEnvio(form) {
    return [...form.querySelectorAll('button:not([type]), button[type="submit"]')];
}

function travar(form, botaoClicado) {
    form.setAttribute(ATRIBUTO, '');
    form.setAttribute('aria-busy', 'true');

    // O disabled só entra depois do submit: botão desabilitado não manda name/value (ex.: acao=enviar).
    setTimeout(() => {
        botoesDeEnvio(form).forEach((botao) => {
            botao.disabled = true;
        });
        botaoClicado?.prepend(criarSpinner());
    }, 0);
}

function destravar(form) {
    form.removeAttribute(ATRIBUTO);
    form.removeAttribute('aria-busy');
    form.querySelectorAll('[data-spinner]').forEach((spinner) => spinner.remove());
    botoesDeEnvio(form).forEach((botao) => {
        botao.disabled = false;
    });
}

document.addEventListener('submit', (event) => {
    const form = event.target;

    // confirm() cancelado ou validação do Alpine: nada a travar.
    if (!(form instanceof HTMLFormElement) || event.defaultPrevented) {
        return;
    }

    if (form.hasAttribute(ATRIBUTO)) {
        event.preventDefault();

        return;
    }

    travar(form, event.submitter);
});

// Voltar pelo botão do navegador restaura a página do cache já travada.
window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        document.querySelectorAll(`form[${ATRIBUTO}]`).forEach(destravar);
    }
});
