<html>
<head>

</head>
<body>
    <h1>INNO<span style="color:red">V</span>AGENDA</h1>
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
    <div>InnoVagenda Time</div>
    <hr/>
    <br/>
    <a href="http://innovagenda.innovative.com.br/agenda">Link Acesso INNOVAGENDA CLOUD</a>
    <hr/>
</body>
</html>