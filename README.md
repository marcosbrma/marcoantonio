# Controle de Gastos Pessoais

Este é um projeto extensionista desenvolvido para a disciplina de **Atividade Extensionista I: Tecnologia Aplicada à Inclusão Digital** da UNINTER.

## Objetivo Extensionista
O objetivo principal deste projeto é aplicar os conhecimentos adquiridos em Análise e Desenvolvimento de Sistemas para ajudar pessoas da comunidade da cidade de São Paulo - SP a organizarem e controlarem melhor suas finanças pessoais. O sistema web desenvolvido permite registrar e acompanhar de maneira simples as entradas (receitas) e saídas (despesas), visando incentivar a educação financeira e promover a inclusão digital por meio de uma ferramenta acessível no dia a dia.

## Tecnologias Utilizadas
- **HTML5**
- **CSS3**
- **PHP**
- **MySQL**

## Link da Aplicação Online
O sistema está hospedado e funcionando através do InfinityFree. Você pode acessá-lo pelo link abaixo:

🔗 **[Acessar Sistema de Controle de Gastos](http://controle-gastos-marco.infinityfreeapp.com/)**

## Instruções de Como Rodar Localmente
1. Certifique-se de ter o **XAMPP** (ou servidor equivalente como o Laragon) instalado em seu computador.
2. Inicie os serviços **Apache** e **MySQL** no painel de controle do XAMPP.
3. Coloque todos os arquivos deste repositório em uma pasta dentro do diretório `htdocs` (ex: `C:\xampp\htdocs\controle-gastos`).
4. Abra seu navegador e acesse o **phpMyAdmin** pelo endereço: `http://localhost/phpmyadmin`
5. Crie um novo banco de dados chamado `controle_gastos`.
6. Importe o arquivo `database.sql` incluído no projeto ou simplesmente copie e cole os comandos SQL para criar a tabela de transações.
7. Acesse o sistema pelo navegador através do link local, por exemplo: `http://localhost/controle-gastos`.
