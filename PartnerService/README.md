# Serviço de Parceiros

A PartnerService é uma biblioteca em PHP que implementa o padrão de projeto Factory Method para criar objetos, no caso Parceiros. Esta biblioteca facilita a integração e a gestão de dados e pedidos de serviço com múltiplos parceiros de forma consistente e extensível, dentro do próprio CodeIgniter4.

## (QuickStart) Criando um novo parceiro

- Crie uma classe dentro da pasta `./PartnerService/Partners/` com o nome do parceiro. `touch ../PartnerService/Partners/Exemplo.php`

- Essa classe precisa extender a classe abstrata PartnerAbstract, e implementar um método protected chamado build().

```PHP
# app/Libraries/Partners/Exemplo.php

<?php

namespace App\Libraries\PartnerService\Partners;

use App\Libraries\PartnerService\Abstracts\PartnerAbstract;

class Exemplo extends PartnerAbstract {}
```

- O método build precisa retornar uma interface de PartnerInterface, que no caso vai ser a classe que vai referênciar o comportamento de todos os parceiros dentro da aplicação.

```PHP
# app/Libraries/Partners/Exemplo.php

<?php

namespace App\Libraries\PartnerService\Partners;

use App\Libraries\PartnerService\Abstracts\PartnerAbstract;use App\Libraries\PartnerService\Interfaces\PartnerInterface;

class Exemplo extends PartnerAbstract 
{
    protected function build(): PartnerInterface
    {
        return new PartnerExemplo();
    }
}
```
- O PartnerExemplo vai ser o criador dos comportamentos, ele precisa implementar a interface PartnerInterface e herdar todos os métodos, mesmo que não sejam utilizados.

```PHP
# app/Libraries/Factory/Concrete/Exemplo.php

<?php

namespace App\Libraries\PartnerService\Factory\Concrete;

use App\Libraries\PartnerService\Interfaces\PartnerInterface;

class Exemplo implements PartnerInterface
{
    public function handleIncomingData(RequestInterface $request)
    {

    }

    public function executeSchedule(array $schedule)
    {

    }

    public function executeScheduleInProgress(array $schedule)
    {

    }

    public function statusNotFound()
    {

    }

    public function send($data, $request)
    {

    }
}
```

- Depois disso basta instanciar o novo parceiro chamando alguma de suas funções principais.

```PHP

<?php

# Receber atualizações dos parceiros sobre as services orders.

$partner = new Exemplo();
$partner->handlePartnerRetrieveData();

```
    
### Referência
Pastas
- *PartnerService:* Library para tratar os parceiros da APVS
- *Factory:* Possui regras para construir parceiros.
- *Abstracts:* Dita qual sequência de execução será seguida.
- *Concrete:* Regras para a criação de um produto específico.
- *Interfaces:* Regras que precisam ser implementadas por todos os parceiros.