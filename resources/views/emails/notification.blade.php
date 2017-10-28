<html>
<head>

</head>
<body>
    <h1><font color="#B22222"><b>B</b></font>ravo - Tax Calendar</h1>
    <div>Prezado Analista,

                segue a lista das atividades em aberto cujo vencimento está proximo ou com prazo vencido:</div>
    <p>
      @foreach ($data['messageLines'] as $messageLine)
        {{ $messageLine }}<br>
      @endforeach
    </p>
    <div>Bom trabalho!</div>
    <br/>
    <div>At.te</div>
    <div>Bravo - Tax Calendar Time</div>
    <hr/>
    <br/>
     <a href="http://innovagenda.innovative.com.br/agenda">Link Acesso Bravo - Tax Calendar CLOUD</a>
    <hr/>
</body>
</html>