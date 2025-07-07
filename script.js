/**
 * Função para mostrar uma mensagem motivacional na página.
 * A mensagem desaparece e o botão reaparece após 10 segundos.
 */
function mostrarMensagem() {

    document.getElementById("mensagem").innerHTML = "O futuro é resultado das escolhas de agora, das sementes plantadas hoje. Comece a enxergar a vida como uma montanha-russa, de altos e baixos. Foque nos seus objetivos e não espere algo incrível para acreditar em um amanhã melhor!";
    
    const botao = document.getElementById("botaoMensagem");
    botao.style.display = "none";
    
    setTimeout(() => {
        botao.style.display = "inline-block"; 
        document.getElementById("mensagem").innerHTML = ""; 
    }, 10000);
}