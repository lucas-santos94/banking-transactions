# Banking Transactions API
Este projeto é uma API Laravel para processamento de transações bancárias. Ele foi desenvolvido para processar transações bancárias como: depósito, saque e transferências entre contas. A API foi desenvolvida para rodar dentro de contêineres Docker, o que facilita a configuração e execução em qualquer ambiente.

## Pré-requisitos
Antes de rodar o projeto, você precisa ter as seguintes ferramentas instaladas:
- **Docker**: Para rodar o projeto em contêineres.
- **Docker Compose**: Para orquestrar os contêineres.
- **PHP**: Para executar os testes e o desenvolvimento localmente.
- **Composer**: Para gerenciar as dependências do PHP.

## Como rodar o projeto com Docker

### Passos:

1. Clone o repositório para o seu computador:

    ```bash
    git clone https://github.com/lucas-santos94/banking-transactions.git
    cd banking-transactions
    ```

2. Certifique-se de ter o **Docker** e o **Docker Compose** instalados em sua máquina.

3. No diretório raiz do projeto, crie o arquivo `.env` baseado no exemplo abaixo:

    ```bash
    cp .env.example .env
    ```

4. Verifique o arquivo `.env` para garantir que as configurações de banco de dados e outros serviços estão corretas para o seu ambiente.

5. Inicie os contêineres com o comando:

    ```bash
    docker-compose up --build
    ```

6. O Docker irá baixar as imagens necessárias e iniciar os contêineres. O servidor Laravel estará disponível em `http://localhost:8080`.

7. Para rodar as migrações e popular o banco de dados com os dados iniciais, execute o comando:

    ```bash
    docker-compose exec banking-transactions php artisan migrate
    docker-compose exec banking-transactions php artisan db:seed
    ```

8. Agora, você pode acessar a API no endereço `http://localhost:8080`.

## Exemplo de arquivo .env

O arquivo `.env` contém variáveis de configuração para a aplicação. Aqui está um exemplo com as configurações principais para o seu projeto:

```env
APP_NAME="Banking Transaction"
APP_ENV=local
APP_KEY=base64:902O8xzH6uGE15twfSUmr0Gbljki64dq9w1EByPDtgY=
APP_DEBUG=true
APP_TIMEZONE=America/Sao_Paulo
APP_URL=http://localhost

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=banking_transactions
DB_USERNAME=banking_transactions
DB_PASSWORD=banking_transactions

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

SESSION_DRIVER=file

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=null
REDIS_DB=0
REDIS_CACHE_DB=1
FILESYSTEM_DISK=local
```

## Exemplo de chamada para a API

```json
// POST /api/account/transactions
// Request body

{
    "transactions": [
        {
            "type": "WITHDRAW", // WITHDRAW || DEPOSIT || TRANSFER
            "amount": 50000, //valor em centavos
            "sourceAccount": "218b56be-7f0d-459f-ad46-39d1f2a30be9" // id da conta
        },
        {
            "type": "TRANSFER",
            "amount": 500000,
            "sourceAccount": "218b56be-7f0d-459f-ad46-39d1f2a30be9",
            "targetAccount": "6aa0ef6e-b382-4653-a551-fba3330e2c2a" //obrigatório se o tipo de transação for TRANSFER
        }
    ]
}

```
A api vai responder 200 e as transações serão enfileiradas e processadas em segundo plano com o Redis

## Testes

Este projeto utiliza o Pest para testes automatizados. Para rodar os testes, siga os passos abaixo:

### Rodando os testes com Pest

```bash
php artisan test
```

# Recuperação de Falhas no Processamento de Transações
Visando garantir o máximo de resiliência ao sistema, foi tomado alguns cuidados para minimizar ao máximo que as transações sejam perdidas devido alguma falha

## Estratégias implementadas

### 1. Uso de transações no banco de dados
Todas transações financeiras são envolvidas em um bloco `DB::transaction`, garantindo que se uma transação falhar durante o processamento, nenhuma alteração parcial será persistida no banco.

A consistencia das contas é preservada evitando débitos e créditos inconsistentes

### 2. Concorrência
Ao processar uma transação, é feito `lockForUpdate()` da conta. Isso impede que outras transações concorrentes resultem em race condition garantindo que apenas uma transação pode modificar o saldo da conta por vez.

### 4. Logs detalhados
Cada transação é registrada na tabela `transactions` com os seguintes dados:
- Valor da transação, taxa e saldo final
- Tipo da transação: depósito, saque ou transaferencia entre contas

Com esses dados é possível fazer uma análise minuciosa em caso de qualquer inconsistencia de saldo

## Mecanismo de Recuperação: Postpone Tasks (Proposta para melhoria)
Além dos mecanismos anteriores, podemos adotar uma abordagem de reprocessamento automático baseada no conceito de **Postpone Tasks**, garantindo que falhas temporárias não resultem em transações perdidas.

### Como Funciona?
1. Se uma transação falhar, ela não é descartada, mas sim enviada para uma fila de reprocessamento.
2. O sistema tentará executar novamente após um intervalo de tempo.
3. Se a transação falhar novamente, ela é registrada para avaliação da equipe e os administradores são notificados.

### Implementação
1. Fila: Jobs de transação são enfileirados no Redis/SQS/Rabbit e executados automaticamente.
2. Retentativas Automáticas: Cada transação pode ser reprocessada até um número máximo de tentativas.
3. Persistência do Estado: O status e motivo das transações falhas é armazenado para monitoramento e análise posterior.

