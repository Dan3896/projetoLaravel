# Projeto Laravel

Aplicação Laravel com banco de dados MySQL e phpMyAdmin rodando em containers Docker. O PHP e o Composer rodam direto na máquina (Windows/PowerShell), sem Laravel Sail.

## Sumário

- [Requisitos](#requisitos)
- [Instalação](#instalação)
- [Configuração do `.env`](#configuração-do-env)
- [Subindo o banco de dados](#subindo-o-banco-de-dados)
- [Rodando o projeto](#rodando-o-projeto)
- [Acessos](#acessos)
- [Comandos úteis](#comandos-úteis)
- [Solução de problemas](#solução-de-problemas)

## Requisitos

- [PHP](https://www.php.net/downloads) 8.2 ou superior, com a extensão `pdo_mysql` ativa
- [Composer](https://getcomposer.org/)
- [Docker Desktop](https://www.docker.com/products/docker-desktop/)
- [Node.js](https://nodejs.org/) e npm (caso o projeto use Vite/assets)

Para conferir se o PHP tem o driver do MySQL:

```powershell
php -m | findstr pdo
```

## Instalação

1. Clone o repositório e entre na pasta:

```powershell
git clone <url-do-repositorio>
cd projetoLaravel
```

2. Instale as dependências:

```powershell
composer install
npm install
```

3. Crie o arquivo `.env` e gere a chave da aplicação:

```powershell
copy .env.example .env
php artisan key:generate
```

## Configuração do `.env`

Como o PHP roda no Windows e o MySQL roda no Docker, o host do banco deve ser `127.0.0.1`. O nome `mysql` só é resolvido **dentro** da rede do Docker e causa o erro `getaddrinfo for mysql failed`.

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=secret
```

Essas credenciais precisam bater com as definidas no `docker-compose.yml` (`MYSQL_DATABASE`, `MYSQL_USER` e `MYSQL_PASSWORD`).

> Use `127.0.0.1` em vez de `localhost`. No Windows, `localhost` pode resolver para IPv6 (`::1`) e a conexão falhar.

## Subindo o banco de dados

Inicie os containers em segundo plano:

```powershell
docker compose up -d
```

Verifique se o container `laravel_mysql` está com status `healthy`:

```powershell
docker compose ps
```

Na primeira execução a inicialização do MySQL pode demorar um pouco.

## Rodando o projeto

Com o banco no ar, limpe o cache de configuração, rode as migrations e inicie o servidor:

```powershell
php artisan config:clear
php artisan migrate
php artisan serve
```

Em outro terminal, se o projeto usar Vite:

```powershell
npm run dev
```

A aplicação fica disponível em `http://127.0.0.1:8000`.

## Acessos

| Serviço    | URL / Host              | Usuário   | Senha    |
|------------|-------------------------|-----------|----------|
| Aplicação  | http://127.0.0.1:8000   | -         | -        |
| phpMyAdmin | http://localhost:8080   | `root`    | `root`   |
| MySQL      | `127.0.0.1:3306`        | `laravel` | `secret` |

> Essas credenciais são apenas para **desenvolvimento local**. Não use em produção.

## Comandos úteis

```powershell
# Parar os containers (mantém os dados)
docker compose stop

# Derrubar os containers (mantém o volume com os dados)
docker compose down

# Derrubar e apagar os dados do banco
docker compose down -v

# Ver logs do MySQL
docker compose logs -f mysql

# Recriar o banco do zero e rodar as migrations
php artisan migrate:fresh

# Recriar o banco com seeders
php artisan migrate:fresh --seed
```

## Solução de problemas

**`getaddrinfo for mysql failed: Este host não é conhecido`**
O `DB_HOST` do `.env` está como `mysql`. Troque para `127.0.0.1` e rode `php artisan config:clear`.

**`Access denied for user 'laravel'`**
As variáveis `MYSQL_USER` e `MYSQL_PASSWORD` só são aplicadas quando o volume `mysql_data` é criado pela primeira vez. Se o volume já existia, recrie-o (isso apaga os dados):

```powershell
docker compose down -v
docker compose up -d
```

Alternativamente, crie o usuário e o banco pelo phpMyAdmin, ou use `root` / `root` no `.env`.

**`could not find driver`**
A extensão `pdo_mysql` não está ativa. Abra o `php.ini` (caminho em `php --ini`), descomente a linha `extension=pdo_mysql` e confira com `php -m | findstr pdo`.

**Conexão recusada ou caindo em outro MySQL**
Se houver um MySQL instalado no Windows (XAMPP, Laragon etc.) usando a porta 3306, ele conflita com o container. Pare o MySQL local ou altere o mapeamento de porta no `docker-compose.yml` para `'3307:3306'` e defina `DB_PORT=3307` no `.env`.

**Erros continuam mesmo após alterar o `.env`**
Limpe o cache de configuração:

```powershell
php artisan config:clear
```

## Licença

Defina aqui a licença do projeto.